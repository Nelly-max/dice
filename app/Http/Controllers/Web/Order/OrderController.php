<?php

namespace App\Http\Controllers\Web\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        $search = trim((string) $request->query('search', ''));

        // rows per page: 10, 15, 25 or 50 (default 15)
        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 15;
        }

        $orders = Order::with('checkoutInvoice')
            ->where('customer_id', $customer->id)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . addcslashes($search, '%_\\') . '%';

                $query->where(function ($q) use ($like) {
                    $q->where('order_id', 'like', $like)
                        ->orWhere('status', 'like', $like)
                        ->orWhere('payment_status', 'like', $like)
                        ->orWhere('delivery_note', 'like', $like)
                        ->orWhereHas('checkoutInvoice', fn ($i) => $i->where('invoice_number', 'like', $like));
                });
            })
            ->latest('placed_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('Hub.orders', compact('orders', 'search', 'perPage'));
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