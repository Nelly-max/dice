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
}