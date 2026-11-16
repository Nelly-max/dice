<?php

namespace App\Http\Controllers\Web\OrderPayment;

use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use App\Models\Customer\CheckoutInvoice;
use App\Models\Customer\MpesaTransactionOrder;

use App\Services\OrderPayment\MpesaService;
use App\Services\OrderPayment\PaymentProcessingService;

class MpesaPaymentController extends Controller
{
    // protected MpesaService $mpesa;

    // public function __construct(MpesaService $mpesa)
    // {
    //     $this->mpesa = $mpesa;
    // }

    public function __construct(
        protected MpesaService $mpesa,
        protected PaymentProcessingService $orderPaymentService
    ) {}


    public function stkPush(Request $request)
    {
        // Strip all spaces from the phone input before validation runs
        if ($request->has('phone')) {
            $request->merge([
                'phone' => str_replace(' ', '', $request->phone),
            ]);
        }

        $request->validate([
            'invoice_id' => ['required', 'integer', 'exists:customer.checkout_invoices,id'],
            'phone' => [
                'required',
                'regex:/^(?:\+254|254|0)?7\d{8}$/'
            ],
        ]);

        $invoice = CheckoutInvoice::findOrFail($request->invoice_id);

        if ($invoice->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This invoice has already been paid.',
            ], 422);
        }

        try {

            $token = $this->mpesa->generateAccessToken();

            $timestamp = $this->mpesa->generateTimestamp();

            $password = $this->mpesa->generatePassword($timestamp);

            $phone = $this->mpesa->formatPhoneNumber($request->phone);

            $amount = $this->mpesa->formatAmount($invoice->total_amount);

            /**
             * Create pending transaction
             */
            $transaction = MpesaTransactionOrder::create([

                'checkout_invoice_id' => $invoice->id,

                'payment_account' => $invoice->payment_account,

                'phone' => $phone,

                'amount' => $amount,

                'payment_method' => 'stk_push',

                'payment_status' => 'pending',
            ]);

            $payload = [

                'BusinessShortCode' => config('mpesa.shortcode'),

                'Password' => $password,

                'Timestamp' => $timestamp,

                'TransactionType' => 'CustomerPayBillOnline',

                'Amount' => $amount,

                'PartyA' => $phone,

                'PartyB' => config('mpesa.shortcode'),

                'PhoneNumber' => $phone,

                'CallBackURL' => config('mpesa.callback_url'),

                'AccountReference' => $invoice->payment_account,

                'TransactionDesc' => 'Order Payment',

            ];

            $http = Http::acceptJson();

            if (config('mpesa.env') === 'sandbox') {
                $http = $http->withoutVerifying();
            }

            $response = $http
                ->withToken($token)
                ->post(
                    'https://' . config('mpesa.env') . '.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
                    $payload
                );

            /**
             * Save request payload
             */
            $transaction->update([
                'stk_request' => $payload,
            ]);

            if (! $response->successful()) {

                $transaction->update([

                    'payment_status' => 'failed',

                    'result_desc' => $response->body(),

                ]);

                Log::error('STK Push Failed', [

                    'invoice_id' => $invoice->id,

                    'status' => $response->status(),

                    'body' => $response->body(),

                ]);

                return response()->json([

                    'success' => false,

                    'message' => 'Unable to initiate payment.',

                ], 500);
            }

            $result = $response->json();

            $transaction->update([

                'merchant_request_id' => $result['MerchantRequestID'] ?? null,

                'checkout_request_id' => $result['CheckoutRequestID'] ?? null,

                'stk_request' => array_merge(
                    $payload,
                    ['response' => $result]
                ),

            ]);

            return response()->json([

                'success' => true,

                'message' => $result['CustomerMessage'] ?? 'STK Push sent successfully.',

                'data' => $result,

            ]);

        } catch (\Throwable $e) {

            Log::error('STK Push Exception', [

                'invoice_id' => $invoice->id,

                'message' => $e->getMessage(),

            ]);

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);
        }
    }

    public function callback(Request $request)
    {
        $payload = $request->all();

        Log::info('M-Pesa Callback Payload', $payload);

        $callback = data_get($payload, 'Body.stkCallback');

        if (! $callback) {
            return response()->json([
                'ResultCode' => 1,
                'ResultDesc' => 'Invalid callback.',
            ]);
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;

        if (! $checkoutRequestId) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $transaction = MpesaTransactionOrder::where(
            'checkout_request_id',
            $checkoutRequestId
        )->first();

        if (! $transaction) {

            Log::warning('Transaction not found.', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Accepted',
            ]);
        }

        /**
         * Settle under a row lock: two copies of the same callback arriving
         * together must not both credit the invoice.
         */
        $outcome = \Illuminate\Support\Facades\DB::connection($transaction->getConnectionName())
            ->transaction(function () use ($transaction, $callback, $payload) {

            $transaction = MpesaTransactionOrder::on($transaction->getConnectionName())
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->first();

            // already settled (success or failure): never settle twice
            if (in_array($transaction->payment_status, ['success', 'failed'], true)) {
                return 'duplicate';
            }

            $data = [
                'result_code'      => $callback['ResultCode'] ?? null,
                'result_desc'      => $callback['ResultDesc'] ?? null,
                'callback_payload' => $payload,
            ];

            $amount = 0;

            if (($callback['ResultCode'] ?? 1) == 0) {

                $metadata = collect(
                    data_get($callback, 'CallbackMetadata.Item', [])
                );

                $amount = (float) (optional(
                    $metadata->firstWhere('Name', 'Amount')
                )['Value'] ?? 0);

                // must be the amount we asked M-Pesa to collect
                $requested = (float) ($transaction->amount ?? 0);

                if ($requested > 0 && (int) round($amount) !== (int) round($requested)) {
                    Log::critical('M-Pesa callback amount differs from the requested amount; not credited.', [
                        'transaction_id' => $transaction->id,
                        'requested'      => $requested,
                        'reported'       => $amount,
                    ]);

                    $transaction->update($data + ['payment_status' => 'failed']);

                    return 'mismatch';
                }

                $data['payment_status'] = 'success';
                $data['mpesa_receipt'] = optional($metadata->firstWhere('Name', 'MpesaReceiptNumber'))['Value'] ?? null;
                $data['amount'] = $amount;
                $data['phone'] = optional($metadata->firstWhere('Name', 'PhoneNumber'))['Value'] ?? null;

                $transactionDate = optional($metadata->firstWhere('Name', 'TransactionDate'))['Value'] ?? null;

                if ($transactionDate) {
                    $data['transaction_date'] = Carbon::createFromFormat('YmdHis', (string) $transactionDate);
                }

            } else {

                $data['payment_status'] = 'failed';

            }

            $transaction->update($data);

            if ($data['payment_status'] === 'success') {
                $this->orderPaymentService->processSuccessfulPayment($transaction, $amount);
            }

            return $data['payment_status'];
        });

        if ($outcome === 'duplicate') {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Already processed']);
        }

        $transaction->refresh();
        $data = ['payment_status' => $transaction->payment_status, 'mpesa_receipt' => $transaction->mpesa_receipt];

        Log::info('M-Pesa transaction updated.', [
            'transaction_id' => $transaction->id,
            'status' => $data['payment_status'],
            'receipt' => $data['mpesa_receipt'] ?? null,
        ]);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

}