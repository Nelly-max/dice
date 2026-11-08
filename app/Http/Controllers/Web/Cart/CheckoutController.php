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
        // Determine subdivision layout
        //------------------------------------------------------------------

        $layout = 'layouts.app'; // default green theme

        if ($cartItems->isNotEmpty()) {
            $dbConnection = $cartItems->first()->subdivision?->db_connection;

            if ($dbConnection === 'homemarket') {
                $layout = 'HomeMarket.layouts.app';
            } elseif ($dbConnection === 'liquorhub') {
                $layout = 'LiquorHub.layouts.app';
            } elseif ($dbConnection === 'glamroom') {
                $layout = 'GlamRoom.layouts.app';
            } elseif ($dbConnection === 'farmmarket') {
                $layout = 'FarmMarket.layouts.app';
            } elseif ($dbConnection === 'water') {
                $layout = 'Water.layouts.app';
            }
        }

        //------------------------------------------------------------------
        // Shipment title from session data
        //------------------------------------------------------------------

        // the selected items grouped by shop (one shipment per shop)
        $shipments = app(\App\Services\ShipmentService::class)->buildShipment($cartItems);

        $shipmentTitle = count($shipments) === 1
            ? $shipments[0]['shop_name']
            : count($shipments) . ' shipments';
        $businessName  = $checkout['shipment'][0]['business_account'] ?? null;

        //------------------------------------------------------------------
        // View
        //------------------------------------------------------------------

        //------------------------------------------------------------------
        // Delivery location: the one the buyer chose while shopping (the
        // cart was built for it). It can't be changed on this page.
        //------------------------------------------------------------------

        $saved = session('delivery_location');
        $deliveryLocation = (is_array($saved) && isset($saved['latitude'], $saved['longitude'])
            && is_numeric($saved['latitude']) && is_numeric($saved['longitude']))
            ? [
                'latitude'  => (float) $saved['latitude'],
                'longitude' => (float) $saved['longitude'],
                'address'   => $saved['address'] ?? null,
                'county'    => $saved['county'] ?? null,
                'town'      => $saved['town'] ?? null,
            ]
            : null;

        return view('Cart.checkout', [
            'deliveryLocation' => $deliveryLocation,
            'cartItems'     => $cartItems,
            'shipments'     => $shipments,
            'shipmentTitle' => $shipmentTitle,
            'businessName'  => $businessName,
            'itemCount'     => $itemCount,
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'delivery'      => $delivery,
            'total'         => $total,
            'expired'       => false,
            'layout'        => $layout,
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

        // no setting for this subdivision: charge by the distance bands (distance_costs)
        if (!$setting) {
            return $this->calculateGeneralDelivery($distance);
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

    private function getSubdivisionDelivery(?int $subdivisionId)
    {
        return SubdivisionDelivery::where(
            'sub_division_id',
            $subdivisionId
        )->first();
    }

    /**
     * Fee for a road distance (km) from the distance_costs bands
     * (distance_from – distance_to, unit km or m, cost). Past the last band
     * the last band's cost applies.
     */
    private function calculateGeneralDelivery(float $distance): array
    {
        $bands = DistanceCost::orderBy('distance_from')->get();

        $inUnit = function ($band) use ($distance) {
            $unit = strtolower(trim((string) $band->unit));
            return in_array($unit, ['m', 'meter', 'meters', 'metre', 'metres'], true) ? $distance * 1000 : $distance;
        };

        $band = $bands->first(fn ($b) => $inUnit($b) >= (float) $b->distance_from && $inUnit($b) <= (float) $b->distance_to)
            ?? $bands->filter(fn ($b) => $inUnit($b) > (float) $b->distance_to)->sortByDesc(fn ($b) => (float) $b->distance_to)->first();

        if (!$band) {
            Log::warning('No delivery band for distance', ['distance_km' => $distance]);
        }

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
        // always the location saved in the session (the one the cart was built for),
        // never coordinates sent by the browser
        $location = app(\App\Services\DeliveryLocationService::class)->get($request);

        if (!$location) {
            return response()->json([
                'code' => 'location_required',
                'message' => 'Set your delivery location first.',
            ], 422);
        }

        $customerLat = $location['latitude'];
        $customerLng = $location['longitude'];

        // cart.js saves the selected shops as checkout.shipment (one entry per shop)
        $shipments = session('checkout.shipment', session('checkout.shipments', []));

        if (empty($shipments)) {
            return response()->json([
                'message' => 'Checkout shipment not found.'
            ], 422);
        }

        $businesses = [];
        $missing = [];      // shops whose road distance could not be worked out
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
                Log::warning('Delivery: shop location missing', ['shipment' => $shipment]);
                $missing[] = $shipment['business_account'] ?? null;
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
                $missing[] = $shipment['business_account'] ?? null;
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

        $complete = empty($missing);

        // the invoice uses this figure, not one sent by the browser (CheckoutControllerV2)
        session(['checkout.delivery' => [
            'fee' => $complete ? $totalFee : null,
            'latitude' => $customerLat,
            'longitude' => $customerLng,
        ]]);

        return response()->json([
            'distance' => $longestDistance,
            'duration' => $longestDuration,
            'fee' => $complete ? $totalFee : null,
            'complete' => $complete,
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
        // same shop + same pin: reuse the answer for 30 minutes (each Routes call is billed)
        $key = 'road_km:' . md5(implode(',', array_map(fn ($n) => round($n, 5), [$fromLat, $fromLng, $toLat, $toLng])));

        if ($cached = \Illuminate\Support\Facades\Cache::get($key)) {
            return $cached;
        }

        try {
            $route = $this->fetchRoadDistance($fromLat, $fromLng, $toLat, $toLng);
        } catch (\Throwable $e) {
            Log::error('Google Routes API unreachable', ['message' => $e->getMessage()]);
            return null;
        }

        if ($route) {
            \Illuminate\Support\Facades\Cache::put($key, $route, now()->addMinutes(30));
        }

        return $route;
    }

    private function fetchRoadDistance(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng
    ): ?array
    {
        $response = Http::timeout(20)
            ->withHeaders([
                'X-Goog-Api-Key' => config('services.google_maps.api_key'),   // env() is empty once config is cached
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
