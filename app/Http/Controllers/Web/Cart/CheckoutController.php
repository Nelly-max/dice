<?php

namespace App\Http\Controllers\Web\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\ProductVariables\DistanceCost;
use App\Models\ProductVariables\SubdivisionDelivery;
use App\Models\ProductVariables\StoreDelivery;
use App\Models\CustomerAddress;
use App\Models\Customer\Cart;

use App\Services\DeliveryService;

class CheckoutController extends Controller
{
    /**
     * Calculates delivery fee using real road routes via Google Distance Matrix API
     */
    public function checkout()
    {
        //------------------------------------------------------------------
        // Selected shipment
        //------------------------------------------------------------------

        $checkout = session('checkout');

        if (
            !$checkout ||
            empty($checkout['shipment']) ||
            !is_array($checkout['shipment'])
        ) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'No shipment selected.');
        }

        //------------------------------------------------------------------
        // Collect selected cart IDs
        //------------------------------------------------------------------

        $cartIds = collect($checkout['shipment'])
            ->pluck('items')
            ->flatten(1)
            ->pluck('cart_id')
            ->unique()
            ->values();

        //------------------------------------------------------------------
        // Load only the selected cart items
        //------------------------------------------------------------------

        $cartItems = Cart::forCurrent()
            ->whereIn('id', $cartIds)
            ->with([
                'stockable',
                'subdivision.shipment',
            ])
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Selected shipment is empty.');
        }

        //------------------------------------------------------------------
        // Totals
        //------------------------------------------------------------------

        $subtotal = $cartItems->sum(function ($item) {
            return ($item->price ?? 0) * $item->quantity;
        });

        $discount = $cartItems->sum(function ($item) {

            return ($item->stockable->discount ?? 0) * $item->quantity;

        });

        $delivery = 0;

        $total = $subtotal + $delivery - $discount;

        $itemCount = $cartItems->sum('quantity');

        //------------------------------------------------------------------
        // View
        //------------------------------------------------------------------

        return view('Cart.checkout', [
            'cartItems'     => $cartItems,
            'shipmentTitle' => 'Shipment',
            'itemCount'     => $itemCount,
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'delivery'      => $delivery,
            'total'         => $total,
            'expired'       => false,
        ]);
    }

    public function selectShipment(Request $request)
    {
        $request->validate([
            'shipment' => ['required', 'array'],
        ]);

        session([
            'checkout.shipment' => $request->shipment,
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    // public function selectShipment(Request $request)
    // {
    //     // dd($request->all());
    //     $request->validate([
    //         'shipments' => ['required', 'array'],
    //         'stockable_ids' => ['required', 'array'],
    //     ]);

    //     session([
    //         'checkout.shipments' => $request->shipments,
    //         'checkout.stockable_ids' => $request->stockable_ids,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //     ]);
    // }

    public function accountDetails()
    {
        $customer = auth('customer')->user();

        return response()->json([
            'name'  => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone_number,
        ]);
    }

    private function getBusinessLocation(?string $connection, ?string $account)
    {
        if (empty($connection) || empty($account)) {
            return null;
        }

        try {

            Log::info('Looking up business', [
                'connection' => $connection,
                'account' => $account,
            ]);

            return DB::connection($connection)
                ->table('business')
                ->select(
                    'account',
                    'name',
                    'latitude',
                    'longitude'
                )
                ->where('account', $account)
                ->first();

        } catch (\Throwable $e) {

            Log::error('Business lookup failed', [
                'connection' => $connection,
                'account' => $account,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }


    private function calculateDeliveryFee(array $shipment, float $distance): array
    {
        
        $setting = $this->getSubdivisionDelivery(
            $shipment['subdivision_id']
        );

        if (!$setting) {
            return [
                'fee' => 0,
                'band' => null,
            ];
        }

        if ($setting->shipment_option === 'general') {
            return $this->calculateGeneralDelivery($distance);
        }

        if ($setting->shipment_option === 'store') {
            return $this->calculateStoreDelivery($shipment, $distance);
        }

        return [
            'fee' => 0,
            'band' => null,
        ];
    }

    private function getSubdivisionDelivery(int $subdivisionId)
    {
        return SubdivisionDelivery::where(
            'sub_division_id',
            $subdivisionId
        )->first();
    }

    private function calculateGeneralDelivery(float $distance): array
    {
        $band = DistanceCost::where('distance_from', '<=', $distance)
            ->where('distance_to', '>=', $distance)
            ->orderBy('distance_from')
            ->first();

        return [
            'fee' => (float) ($band->cost ?? 0),
            'band' => $band,
        ];
    }

    private function calculateStoreDelivery(array $shipment, float $distance): array
    {
        $store = StoreDelivery::where(
                'sub_division_id',
                $shipment['subdivision_id']
            )
            ->where(
                'business_account',
                $shipment['business_account']
            )
            ->first();

        if (!$store) {
            return [
                'fee' => 0,
                'band' => null,
            ];
        }

        if (!$store->delivery_charge) {
            return [
                'fee' => 0,
                'band' => null,
            ];
        }

        if ($distance < $store->charge_at) {
            return [
                'fee' => 0,
                'band' => null,
            ];
        }

        // Charge exactly like a normal subdivision
        return $this->calculateGeneralDelivery($distance);
    }

    public function calculateDistance(Request $request)
    {
        $customerLat = (float) $request->latitude;
        $customerLng = (float) $request->longitude;

        $shipments = session('checkout.shipments', []);

        if (empty($shipments)) {
            return response()->json([
                'message' => 'Checkout shipment not found.'
            ], 422);
        }

        $businesses = [];
        $totalFee = 0;
        $longestDistance = 0;
        $longestDuration = 0;

        foreach ($shipments as $shipment) {

            $business = $this->getBusinessLocation(
                $shipment['db_connection'],
                $shipment['business_account']
            );

            if (
                !$business ||
                empty($business->latitude) ||
                empty($business->longitude)
            ) {
                continue;
            }

            //--------------------------------------------------------
            // Road distance instead of Haversine
            //--------------------------------------------------------

            $route = $this->getRoadDistance(
                (float) $business->latitude,
                (float) $business->longitude,
                $customerLat,
                $customerLng
            );

            if (!$route) {
                continue;
            }

            $distance = $route['distance'];
            $duration = $route['duration'];

            //--------------------------------------------------------
            // Delivery fee
            //--------------------------------------------------------

            $delivery = $this->calculateDeliveryFee(
                $shipment,
                $distance
            );

            $fee = $delivery['fee'];
            $band = $delivery['band'];

            $totalFee += $fee;

            $longestDistance = max($longestDistance, $distance);
            $longestDuration = max($longestDuration, $duration);

            //--------------------------------------------------------
            // Store business summary
            //--------------------------------------------------------

            $businesses[] = [
                'account' => $business->account,
                'name' => $business->name,
                'latitude' => (float) $business->latitude,
                'longitude' => (float) $business->longitude,
                'distance' => $distance,
                'duration' => $duration,
                'fee' => $fee,
                'band' => $band ? [
                    'from' => $band->distance_from,
                    'to' => $band->distance_to,
                    'unit' => $band->unit,
                ] : null,
            ];
        }

        return response()->json([
            'distance' => $longestDistance,
            'duration' => $longestDuration,
            'fee' => $totalFee,
            'businesses' => $businesses,
        ]);
    }


    private function getRoadDistance(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng
    ): ?array
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'X-Goog-Api-Key' => env('GOOGLE_MAPS_API_KEY'),
                'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration',
            ])
            ->post('https://routes.googleapis.com/directions/v2:computeRoutes', [

                'origin' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $fromLat,
                            'longitude' => $fromLng,
                        ],
                    ],
                ],

                'destination' => [
                    'location' => [
                        'latLng' => [
                            'latitude' => $toLat,
                            'longitude' => $toLng,
                        ],
                    ],
                ],

                'travelMode' => 'DRIVE',

                'routingPreference' => 'TRAFFIC_UNAWARE',

                'computeAlternativeRoutes' => false,

                'languageCode' => 'en-US',

                'units' => 'METRIC',
            ]);

        if (!$response->successful()) {

            Log::error('Google Routes API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        }

        $route = $response->json()['routes'][0] ?? null;

        if (!$route) {
            return null;
        }

        return [
            'distance' => round(($route['distanceMeters'] ?? 0) / 1000, 2),
            'duration' => isset($route['duration'])
                ? round((int) rtrim($route['duration'], 's') / 60)
                : 0,
        ];
    }



// private function haversine($lat1, $lon1, $lat2, $lon2)
// {
//     $earthRadius = 6371;

//     $latDelta = deg2rad($lat2 - $lat1);
//     $lonDelta = deg2rad($lon2 - $lon1);

//     $a = sin($latDelta / 2) * sin($latDelta / 2) +
//          cos(deg2rad($lat1)) *
//          cos(deg2rad($lat2)) *
//          sin($lonDelta / 2) *
//          sin($lonDelta / 2);

//     $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

//     return $earthRadius * $c;
// }
}
