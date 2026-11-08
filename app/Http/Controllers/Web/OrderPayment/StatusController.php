<?php

namespace App\Http\Controllers\Web\OrderPayment;

use App\Http\Controllers\Controller;

use App\Models\Customer\Order;
use App\Models\Customer\CheckoutInvoice;
use App\Models\Customer\MpesaTransactionOrder;
use App\Services\OrderPayment\MpesaService;
use App\Services\OrderPayment\PaymentProcessingService;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Polled by resources/js/orderPayment.js every few seconds while the customer
 * is on the M-Pesa page. Read only: it reports what the M-Pesa callbacks have
 * already saved, so the page can show each step (prompt sent, waiting for PIN,
 * cancelled, part paid, paid).
 *
 * If the M-Pesa callback has not arrived for a pending STK prompt (callback URL
 * not reachable, e.g. on a local machine), it asks Safaricom for the result
 * (STK Push Query) and records it exactly as the callback would. The callback
 * and this check never both credit the invoice: whichever runs first marks the
 * transaction 'success', and the other one skips it.
 */
class StatusController extends Controller
{
    public function __construct(
        protected MpesaService $mpesa,
        protected PaymentProcessingService $orderPaymentService
    ) {}

    public function status(CheckoutInvoice $invoice)
    {
        $this->checkPendingPrompt($invoice);
        $invoice->refresh();

        $total = (float) $invoice->total_amount;
        $paid = (float) ($invoice->amount_paid ?? 0);

        $response = [
            'paid' => false,
            'status' => $invoice->status,
            'total' => $total,
            'amount_paid' => $paid,
            'balance' => max(0, $total - $paid),
            'stk' => null,
        ];

        if ($invoice->status === 'order-placed') {

            $order = Order::where(
                'checkout_invoice_id',
                $invoice->id
            )->first();

            $response['paid'] = true;
            $response['redirect'] = $order ? route('orders.placed', $order) : null;
        }

        // the latest STK prompt for this invoice (its callback fills result_code / result_desc)
        $stk = MpesaTransactionOrder::where('checkout_invoice_id', $invoice->id)
            ->where('payment_method', 'stk_push')
            ->latest('id')
            ->first();

        if ($stk) {
            $response['stk'] = [
                'checkout_request_id' => $stk->checkout_request_id,
                'state' => $stk->payment_status,          // pending | success | failed
                'code' => $stk->result_code,
                'message' => $stk->result_desc,
                'receipt' => $stk->mpesa_receipt,
            ];
        }

        return response()->json($response);
    }

    /**
     * Pending STK prompt without a callback yet: ask Safaricom (at most every 5 seconds per prompt).
     */
    private function checkPendingPrompt(CheckoutInvoice $invoice): void
    {
        $stk = MpesaTransactionOrder::where('checkout_invoice_id', $invoice->id)
            ->where('payment_method', 'stk_push')
            ->latest('id')
            ->first();

        if (
            ! $stk
            || $stk->payment_status !== 'pending'
            || ! $stk->checkout_request_id
            || $stk->created_at?->gt(now()->subSeconds(8))
            || ! Cache::add('mpesa-stk-query:' . $stk->id, 1, 5)
        ) {
            return;
        }

        try {
            $result = $this->mpesa->stkQuery($stk->checkout_request_id);
        } catch (\Throwable $e) {
            Log::warning('M-Pesa STK query error', ['transaction_id' => $stk->id, 'message' => $e->getMessage()]);
            return;
        }

        if ($result === null) {
            return; // the customer has not answered yet
        }

        // the payment row is in the finance database and the invoice / order in
        // customer: hold the row lock (finance) for the whole update (customer)
        DB::connection('finance')->transaction(fn () => DB::connection('customer')->transaction(function () use ($stk, $result) {

            // the callback may have landed meanwhile
            $transaction = MpesaTransactionOrder::whereKey($stk->id)->lockForUpdate()->first();

            if (! $transaction || $transaction->payment_status !== 'pending') {
                return;
            }

            $paid = $result['code'] === 0;

            $transaction->update([
                'payment_status' => $paid ? 'success' : 'failed',
                'result_code' => $result['code'],
                'result_desc' => $result['desc'],
            ]);

            Log::info('M-Pesa transaction updated from STK query.', [
                'transaction_id' => $transaction->id,
                'status' => $transaction->payment_status,
            ]);

            if ($paid) {
                $this->orderPaymentService->processSuccessfulPayment(
                    $transaction,
                    (float) $transaction->amount
                );
            }
        }));
    }
}
