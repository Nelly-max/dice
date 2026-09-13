<?php

namespace App\Http\Controllers\Web\Payment;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\Customer\CheckoutInvoice;

class PaymentController extends Controller
{
    // public function mpesa(Request $request)
    // {
    //     $invoiceNumber = $request->invoice;


    //     if (!$invoiceNumber) {

    //         return redirect()
    //             ->route('cart.checkout')
    //             ->with('error', 'Invoice number missing.');

    //     }


    //     $invoice = CheckoutInvoice::where('invoice_number', $invoiceNumber)
    //         ->firstOrFail();


    //     return view('payment.mpesa', [
    //         'invoice' => $invoice,
    //         'paybill' => config('payment.mpesa_paybill', '117450'),
    //     ]);
    // }

    public function mpesa($invoice)
    {
        $invoice = CheckoutInvoice::where('uuid', $invoice)
            ->firstOrFail();

        return view('payment.mpesa', compact('invoice'));
    }
}