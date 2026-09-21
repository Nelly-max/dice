<?php

namespace App\Http\Controllers\Web\Order;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Models\Customer\Order;
use App\Models\Customer\CheckoutInvoice;

class OrderController extends Controller
{

    public function orderPlaced(Order $order)
    {
        return view('orders.success', compact('order'));
    }

    /**
     * List customer's orders.
     */
    public function index()
    {
        $customer = Auth::guard('customer')->user();

        $orders = Order::where('customer_id', $customer->id)
            ->latest('placed_at')
            ->paginate(15);

        return view('Hub.orders', compact('orders'));
    }

    /**
     * View a single order.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Create an order from a checkout invoice.
     *
     * Normally this is called by PaymentProcessingService
     * after payment is confirmed or for Cash on Delivery.
     */
    public function store(CheckoutInvoice $invoice)
    {
        //
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order)
    {
        //
    }

    /**
     * Mark an order as completed.
     */
    public function complete(Order $order)
    {
        //
    }
}