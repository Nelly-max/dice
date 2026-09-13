<?php

namespace App\Http\Controllers\Web\OrderPayment;

use App\Http\Controllers\Controller;


use App\Models\Customer\Order;
use App\Models\Customer\CheckoutInvoice;


class StatusController extends Controller
{
    public function status(CheckoutInvoice $invoice)
    {
        if ($invoice->status === 'order-placed') {

            $order = Order::where(
                'checkout_invoice_id',
                $invoice->id
            )->first();

            return response()->json([
                'paid' => true,
                'redirect' => route('orders.placed', $order),
            ]);
        }

        return response()->json([
            'paid' => false,
        ]);
    }
}