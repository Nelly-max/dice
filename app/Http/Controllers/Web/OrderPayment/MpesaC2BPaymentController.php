<?php

namespace App\Http\Controllers\Web\OrderPayment;

use App\Http\Controllers\Controller;
use App\Services\OrderPayment\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MpesaC2BPaymentController extends Controller
{
    protected MpesaService $mpesaService;

    /**
     * Inject the updated MpesaService.
     */
    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Handshake Trigger: Registers Validation and Confirmation URLs with Safaricom.
     * Endpoint: POST /api/mpesa/c2b/register
     */
    public function register(): JsonResponse
    {
        $response = $this->mpesaService->registerUrls();
        
        return response()->json($response);
    }

    /**
     * 1. Safaricom VALIDATION Webhook (Pre-auth checkpoint)
     * Safaricom pings this to check if the payment should be accepted or rejected.
     * Endpoint: POST /api/mpesa/c2b/validation
     */
    public function validatePayment(Request $request): JsonResponse
    {
        Log::info('M-Pesa C2B Validation Payload Received:', $request->all());

        $accountNumber = trim($request->input('BillRefNumber'));
        $amount = $request->input('TransAmount');

        // TODO: Add database validation rule checks here.
        // Example: Reject if the order or account number doesn't exist in your database
        // if (!Order::where('id', $accountNumber)->exists()) {
        //     return response()->json([
        //         'ResultCode' => 'C2B00011', // Safaricom code for Invalid Account Number
        //         'ResultDesc' => 'Rejected. Account number does not exist.'
        //     ]);
        // }

        // Accept the transaction request package
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted'
        ]);
    }

    /**
     * 2. Safaricom CONFIRMATION Webhook (State resolution checkpoint)
     * Safaricom sends this when the payment clears. Use this to credit your users.
     * Endpoint: POST /api/mpesa/c2b/confirmation
     */
    public function confirmPayment(Request $request): JsonResponse
    {
        Log::info('M-Pesa C2B Confirmation Payload Received:', $request->all());

        $receipt       = $request->input('TransID');       // M-Pesa Receipt Code (e.g., RQA1234567)
        $amount        = $request->input('TransAmount');   // Paid Amount
        $accountNumber = trim($request->input('BillRefNumber')); // Typed Account ID / Reference
        $phoneNumber   = $request->input('MSISDN');        // Customer Phone Number

        Log::info("C2B Credit Successful: Receipt {$receipt} | Account {$accountNumber} | KES {$amount}");

        try {
            // TODO: Process your database transaction logging records here
            // Example:
            // Payment::create([
            //     'transaction_id' => $receipt,
            //     'amount' => $amount,
            //     'account_number' => $accountNumber,
            //     'phone' => $phoneNumber,
            //     'status' => 'completed'
            // ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save M-Pesa C2B payment records: ' . $e->getMessage());
            // Note: Safaricom expects a success payload once confirmation drops to close the loop
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Success'
        ]);
    }

    /**
     * Admin Sandbox Testing Tool: Simulates a manual customer payment event.
     * Endpoint: POST /api/mpesa/c2b/simulate
     */
    public function simulate(Request $request): JsonResponse
    {
        $request->validate([
            'phone'          => 'required|string',
            'amount'         => 'required|numeric|min:1',
            'account_number' => 'required|string',
        ]);

        $response = $this->mpesaService->simulateTransaction(
            $request->input('phone'),
            $request->input('amount'),
            $request->input('account_number')
        );

        return response()->json($response);
    }
}
