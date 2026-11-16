<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

use App\Models\Hub\Rider;
use App\Models\Customer\Delivery;

class DeliveryController extends Controller
{
    public function deliveries()
    {
        $customer = Auth::guard('customer')->user();

        $rider = Rider::where('customer_id', $customer->id)->first();

        if (!$rider) {
            return view('Hub.deliveries', [
                'deliveries' => collect(),
            ]);
        }

        $deliveries = Delivery::query()
            ->where('rider_account', $rider->rider_account)
            ->latest('created_at')
            ->get();

        return view('Hub.deliveries', compact('deliveries'));
    }

    /** One of the rider's deliveries: route, people, times and the order's delivery fee and note. */
    public function show(int $delivery)
    {
        $customer = Auth::guard('customer')->user();
        $rider = Rider::where('customer_id', $customer->id)->firstOrFail();

        $delivery = Delivery::query()
            ->where('rider_account', $rider->rider_account)
            ->findOrFail($delivery);

        $order = null;

        try {
            $order = optional($delivery->order)->order;
        } catch (\Throwable $e) {
            report($e);
        }

        return view('Hub.delivery', compact('delivery', 'order'));
    }
}
