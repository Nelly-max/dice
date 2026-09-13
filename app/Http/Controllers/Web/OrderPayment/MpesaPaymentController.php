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
         * Prevent duplicate processing.
         */
        if ($transaction->payment_status === 'success') {

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Already processed',
            ]);

        }

        $data = [
            'result_code'      => $callback['ResultCode'] ?? null,
            'result_desc'      => $callback['ResultDesc'] ?? null,
            'callback_payload' => $payload,
        ];

        if (($callback['ResultCode'] ?? 1) == 0) {

            $metadata = collect(
                data_get($callback, 'CallbackMetadata.Item', [])
            );

            $amount = optional(
                $metadata->firstWhere('Name', 'Amount')
            )['Value'] ?? 0;

            $receipt = optional(
                $metadata->firstWhere('Name', 'MpesaReceiptNumber')
            )['Value'] ?? null;

            $transactionDate = optional(
                $metadata->firstWhere('Name', 'TransactionDate')
            )['Value'] ?? null;

            $phone = optional(
                $metadata->firstWhere('Name', 'PhoneNumber')
            )['Value'] ?? null;

            $data['payment_status'] = 'success';
            $data['mpesa_receipt'] = $receipt;
            $data['amount'] = $amount;
            $data['phone'] = $phone;

            if ($transactionDate) {
                $data['transaction_date'] = Carbon::createFromFormat(
                    'YmdHis',
                    $transactionDate
                );
            }

        } else {

            $data['payment_status'] = 'failed';

        }

        /**
         * Update transaction.
         */
        $transaction->update($data);

        /**
         * Delegate business logic to the service.
         */
        if ($data['payment_status'] === 'success') {

            $this->orderPaymentService->processSuccessfulPayment(
                $transaction,
                $amount
            );

        }

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