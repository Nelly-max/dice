<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;

use App\Models\Customer\Delivery;
use App\Models\Hub\Rider;
use App\Models\Hub\RiderLocation;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiderLocationController extends Controller
{

    private function rider(): Rider
    {
        return Rider::where(
            'customer_id',
            Auth::guard('customer')->id()
        )->firstOrFail();
    }

    public function init()
    {
        $customerId = Auth::guard('customer')->id();

        $rider = Rider::where('customer_id', $customerId)->first();

        if (!$rider) {
            return response()->json([
                'has_rider_account' => false,
            ]);
        }

        $location = RiderLocation::firstOrCreate(
            ['rider_id' => $rider->id],
            [
                'status' => 'offline',
                'last_seen' => now(),
            ]
        );

        return response()->json([
            'has_rider_account' => true,
            'account_status'    => $rider->account_status,
            'online_status'     => $location->status,
        ]);
    }

    public function updateRiderLocation(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'heading'   => 'nullable|numeric',
            'speed'     => 'nullable|numeric',
            'accuracy'  => 'nullable|numeric',
        ]);

        $customerId = Auth::guard('customer')->id();

        if (!$customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated customer.',
            ], 401);
        }

        $rider = Rider::where('customer_id', $customerId)->first();

        if (!$rider) {
            return response()->json([
                'success' => false,
                'message' => 'Rider profile not found.',
            ], 404);
        }

        $location = RiderLocation::updateOrCreate(
            [
                'rider_id' => $rider->id,
            ],
            [
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
                'heading'   => $request->heading,
                'speed'     => $request->speed,
                'accuracy'  => $request->accuracy,
                'last_seen' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'rider_id' => $rider->id,
            'location' => [
                'latitude'  => $location->latitude,
                'longitude' => $location->longitude,
                'heading'   => $location->heading,
                'speed'     => $location->speed,
                'accuracy'  => $location->accuracy,
                'last_seen' => $location->last_seen,
            ],
        ]);
    }

    public function getStatus()
    {
        $rider = Rider::where(
            'customer_id',
            Auth::guard('customer')->id()
        )->firstOrFail();


        $location = RiderLocation::where(
            'rider_id',
            $rider->id
        )->first();


        return response()->json([

            'status' => $location?->status ?? 'offline'

        ]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:online,offline,busy',
        ]);

        $rider = Rider::where(
            'customer_id',
            Auth::guard('customer')->id()
        )->first();

        if (!$rider) {

            return response()->json([
                'success' => false,
                'message' => 'Rider account not found.',
            ], 404);

        }

        /*
        |--------------------------------------------------------------------------
        | Only active riders may become online or busy
        |--------------------------------------------------------------------------
        */

        if (
            in_array($request->status, ['online', 'busy']) &&
            $rider->account_status !== 'active'
        ) {

            if ($rider->account_status === 'suspended') {

                return response()->json([
                    'success' => false,
                    'message' => 'Your rider account has been temporarily suspended. Please contact support.',
                ], 403);

            }

            return response()->json([
                'success' => false,
                'message' => 'Your rider account is not active.',
            ], 403);

        }

        RiderLocation::updateOrCreate(

            [
                'rider_id' => $rider->id,
            ],

            [
                'status' => $request->status,
                'last_seen' => now(),
            ]

        );

        return response()->json([

            'success' => true,
            'status' => $request->status,

        ]);
    }


public function navigate(Delivery $delivery)
{
    $rider = $this->rider();


    /*
    |--------------------------------------------------------------------------
    | Make sure this delivery belongs to this rider
    |--------------------------------------------------------------------------
    */

    if (
        $delivery->rider_account !==
        $rider->rider_account
    ) {

        abort(403, 'This delivery is not assigned to you.');

    }


    /*
    |--------------------------------------------------------------------------
    | Do not allow navigation for completed deliveries
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            $delivery->status,
            [
                'delivered',
                'completed',
                'canceled'
            ],
            true
        )
    ) {

        abort(
            404,
            'This delivery is no longer active.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Get rider's latest location
    |--------------------------------------------------------------------------
    */

    $riderLocation =
        RiderLocation::where(
            'rider_id',
            $rider->id
        )
        ->latest('last_seen')
        ->firstOrFail();


    /*
    |--------------------------------------------------------------------------
    | Delivery navigation map
    |--------------------------------------------------------------------------
    */

    return view(
        'Hub.deliveryMap',
        [
            'delivery' =>
                $delivery,

            'riderLocation' =>
                $riderLocation,
        ]
    );
}

}