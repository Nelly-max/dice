<?php

namespace App\Http\Controllers\Web\OrderPayment;

use App\Http\Controllers\Controller;
use App\Models\Customer\CheckoutInvoice;
use App\Models\Customer\MpesaTransactionOrder;
use App\Services\OrderPayment\PaymentProcessingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Paybill payments (C2B): the customer pays from the M-Pesa menu with the invoice's
 * payment account as the account number.
 *
 * Both endpoints are called by Safaricom only, through /pay/hook/c2b/... (guarded by
 * App\Http\Middleware\VerifySafaricomCallback: secret key in the address + Safaricom IP).
 * The addresses are registered once with:  php artisan mpesa:register-urls
 */
class MpesaC2BPaymentController extends Controller
{
    public function __construct(
        protected PaymentProcessingService $orderPaymentService
    ) {}

    /**
     * Safaricom asks before taking the money (only if "external validation" is switched on
     * for the shortcode): accept only an account number that belongs to an unpaid invoice.
     * Route: POST /pay/hook/c2b/validate/{key}
     */
    public function validatePayment(Request $request): JsonResponse
    {
        Log::info('M-Pesa C2B validation', $request->only(['TransID', 'TransAmount', 'BillRefNumber']));

        $invoice = $this->openInvoice((string) $request->input('BillRefNumber'));

        if (! $invoice) {
            return response()->json([
                'ResultCode' => 'C2B00012',            // invalid account number
                'ResultDesc' => 'Rejected',
            ]);
        }

        if ((float) $request->input('TransAmount') < 1) {
            return response()->json([
                'ResultCode' => 'C2B00013',            // invalid amount
                'ResultDesc' => 'Rejected',
            ]);
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /**
     * Safaricom reports a completed Paybill payment: credit the invoice once per M-Pesa
     * receipt (TransID). A part payment leaves the invoice "partial"; the order is placed
     * when the total is reached (PaymentProcessingService).
     * Route: POST /pay/hook/c2b/confirm/{key}
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        $receipt = trim((string) $request->input('TransID'));
        $amount = (float) $request->input('TransAmount');
        $account = (string) $request->input('BillRefNumber');
        $phone = (string) $request->input('MSISDN');

        Log::info('M-Pesa C2B confirmation', ['TransID' => $receipt, 'TransAmount' => $amount, 'BillRefNumber' => $account]);

        if ($receipt === '' || $amount <= 0) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        try {
            $outcome = DB::connection('finance')->transaction(fn () => DB::connection('customer')->transaction(function () use ($request, $receipt, $amount, $account, $phone) {

                // the same receipt twice (Safaricom retries): credit it once
                if (MpesaTransactionOrder::where('mpesa_receipt', $receipt)->lockForUpdate()->exists()) {
                    return 'duplicate';
                }

                $invoice = $this->openInvoice($account, true);

                if (! $invoice) {
                    // money received with an account number we do not know: kept in the log
                    // for the finance team to refund or match by hand
                    Log::warning('M-Pesa C2B payment with no matching invoice.', $request->all());
                    return 'unmatched';
                }

                $transaction = MpesaTransactionOrder::create([
                    'checkout_invoice_id' => $invoice->id,
                    'payment_account' => $invoice->payment_account,
                    'mpesa_receipt' => $receipt,
                    'amount' => $amount,
                    'phone' => $phone !== '' ? substr($phone, 0, 20) : null,
                    'payment_method' => 'paybill',
                    'payment_status' => 'success',
                    'transaction_date' => $this->transTime((string) $request->input('TransTime')),
                    'result_code' => 0,
                    'result_desc' => 'Paybill payment',
                    'callback_payload' => $request->all(),
                ]);

                $this->orderPaymentService->processSuccessfulPayment($transaction, $amount);

                return 'credited';
            }));
        } catch (\Throwable $e) {
            Log::error('M-Pesa C2B confirmation failed.', ['TransID' => $receipt, 'message' => $e->getMessage()]);
            $outcome = 'error';
        }

        Log::info('M-Pesa C2B confirmation handled.', ['TransID' => $receipt, 'outcome' => $outcome]);

        // Safaricom only needs to know we received it
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * The newest unpaid invoice with this payment account (accounts repeat on the same
     * day of a later year, so only the last 60 days count).
     */
    private function openInvoice(string $account, bool $lock = false): ?CheckoutInvoice
    {
        $account = strtoupper(preg_replace('/\s+/', '', $account));

        if ($account === '') {
            return null;
        }

        return CheckoutInvoice::where('payment_account', $account)
            ->where('payment_method', 'mpesa')
            ->whereIn('status', ['pending', 'partial'])
            ->where('created_at', '>=', now()->subDays(60))
            ->latest('id')
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first();
    }

    private function transTime(string $value): ?Carbon
    {
        try {
            return $value !== '' ? Carbon::createFromFormat('YmdHis', $value) : now();
        } catch (\Throwable $e) {
            return now();
        }
    }
}
