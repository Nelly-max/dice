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


class CheckoutController extends Controller
{
    /** Why the last road distance failed (getRoadDistance), for the checkout's `problems`. */
    private ?array $routeError = null;

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

        // the shop may have sold out or lowered its stock since: bring the cart in line, hold it
        // again for the checkout, and if anything changed send the buyer back to see it
        $stock = app(\App\Services\Cart\CartStock::class)->sync($cartItems, true);
        if ($stock['changed']) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Some items in your cart changed because the shop has less in stock. Please check your cart.');
        }

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


    public function accountDetails()
    {
        $customer = auth('customer')->user();

        return response()->json([
            'name'  => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone_number,
        ]);
    }

    /**
     * The shops to deliver from, worked out on the server from the customer's own cart
     * lines: [subdivision_id, business_account, db_connection, cart_ids] per shop.
     * Only the cart ids are read from the checkout payload.
     */
    private function serverShipments($payload): array
    {
        $cartIds = collect(is_array($payload) ? $payload : [])
            ->pluck('items')
            ->flatten(1)
            ->pluck('cart_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($cartIds->isEmpty()) {
            return [];
        }

        $lines = Cart::forCurrent()->whereIn('id', $cartIds)->get(['id', 'sub_division_id', 'business_account']);

        $connections = DB::table('sub_divisions')
            ->whereIn('id', $lines->pluck('sub_division_id')->filter()->unique())
            ->pluck('db_connection', 'id');

        return $lines
            ->filter(fn ($line) => $line->sub_division_id && $line->business_account && isset($connections[$line->sub_division_id]))
            ->groupBy(fn ($line) => $line->sub_division_id . '|' . $line->business_account)
            ->map(fn ($group) => [
                'subdivision_id' => (int) $group->first()->sub_division_id,
                'business_account' => (string) $group->first()->business_account,
                'db_connection' => (string) $connections[$group->first()->sub_division_id],
                'cart_ids' => $group->pluck('id')->all(),
            ])
            ->values()
            ->all();
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
        // the county the map gave for the pin (DeliveryLocationController), for parcel zones
        $customer = $location + ['county' => session('delivery_location.county')];
        $countrywide = app(\App\Services\CountrywideDelivery::class);

        // cart.js saves the selected shops as checkout.shipment (one entry per shop). Only
        // the cart lines are taken from it: which shop, subdivision and database each line
        // belongs to comes from the cart itself (serverShipments), so a changed payload
        // cannot point the fee at a cheaper or free-delivery shop.
        $shipments = $this->serverShipments(session('checkout.shipment', session('checkout.shipments', [])));

        if (empty($shipments)) {
            return response()->json([
                'message' => 'Checkout shipment not found.'
            ], 422);
        }

        $businesses = [];
        $missing = [];      // shops whose road distance could not be worked out
        $problems = [];     // why, per shop (shown in the browser console by checkout.js)
        $totalFee = 0;
        $longestDistance = 0;
        $longestDuration = 0;
        $parcelDays = null;  // slowest parcel [min, max] days, when any shop sends one

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
                $problems[] = [
                    'account' => $shipment['business_account'] ?? null,
                    'shop' => $business->name ?? null,
                    'reason' => $business ? 'shop_location_missing' : 'shop_not_found',
                ];
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
                $problems[] = [
                    'account' => $business->account,
                    'shop' => $business->name,
                    // google_refused (key / API / billing, see `google`), google_unreachable or no_route
                    'reason' => $this->routeError['reason'] ?? 'no_route',
                    'google' => $this->routeError['google'] ?? null,
                ];
                continue;
            }

            $distance = $route['distance'];
            $duration = $route['duration'];

            //--------------------------------------------------------
            // Delivery fee: a rider within the market's radius (road
            // distance bands); further, for a countrywide market, a parcel
            //--------------------------------------------------------

            $method = 'rider';
            $parcel = null;
            $market = $countrywide->market((int) $shipment['subdivision_id']);

            if ($countrywide->beyondRiders($market, $distance)) {
                $lines = Cart::forCurrent()->whereIn('id', $shipment['cart_ids'])->with('stockable')->get();
                $parcel = $countrywide->quote($shipment, $business, $customer, $lines);

                if (!$parcel['ok']) {
                    $missing[] = $business->account;
                    $problems[] = [
                        'account' => $business->account,
                        'shop' => $business->name,
                        // shop_not_countrywide, products_not_countrywide (+ products), county_unknown, zone_missing, no_parcel_rate
                        'reason' => $parcel['reason'],
                        'products' => $parcel['products'] ?? [],
                        'radius_km' => (float) $market->local_radius_km,
                        'distance' => $distance,
                    ];
                    continue;
                }

                $method = 'parcel';
                $delivery = ['fee' => $parcel['fee'], 'band' => null];
                $parcelDays = [max($parcelDays[0] ?? 0, $parcel['days_min']), max($parcelDays[1] ?? 0, $parcel['days_max'])];
            } else {
                $delivery = $this->calculateDeliveryFee(
                    $shipment,
                    $distance
                );
            }

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
                'method' => $method,
                'parcel' => $parcel ? [
                    'from' => $parcel['zone_from'],
                    'to' => $parcel['zone_to'],
                    'eta' => $parcel['eta'],
                ] : null,
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
            // rider or parcel per shop, for the order and the shop's dispatch
            'methods' => collect($businesses)->pluck('method', 'account')->all(),
            'latitude' => $customerLat,
            'longitude' => $customerLng,
            // the cart lines this fee is for: the invoice refuses a quote for other lines
            'cart_ids' => collect($shipments)->pluck('cart_ids')->flatten()->map(fn ($id) => (int) $id)->sort()->values()->all(),
        ]]);

        return response()->json([
            'distance' => $longestDistance,
            'duration' => $longestDuration,
            'fee' => $complete ? $totalFee : null,
            'complete' => $complete,
            'parcel_eta' => $parcelDays ? ($parcelDays[0] === $parcelDays[1] ? $parcelDays[1] . ' ' . ($parcelDays[1] === 1 ? 'day' : 'days') : "{$parcelDays[0]}–{$parcelDays[1]} days") : null,
            'businesses' => $businesses,
            'problems' => $problems,
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

        $this->routeError = null;

        try {
            $route = $this->fetchRoadDistance($fromLat, $fromLng, $toLat, $toLng);
        } catch (\Throwable $e) {
            Log::error('Google Routes API unreachable', ['message' => $e->getMessage()]);
            $this->routeError = ['reason' => 'google_unreachable'];
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
                // server key (GOOGLE_MAPS_SERVER_KEY, else the browser key); env() is empty once config is cached
                'X-Goog-Api-Key' => config('services.google_maps.server_key') ?: config('services.google_maps.api_key'),
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

            // e.g. PERMISSION_DENIED "Requests from referer <empty> are blocked." (a browser-only key)
            // or "Routes API has not been used in project … or it is disabled"
            $this->routeError = [
                'reason' => 'google_refused',
                'google' => [
                    'http' => $response->status(),
                    'status' => $response->json('error.status'),
                    'message' => $response->json('error.message'),
                    // Google's exact cause, e.g. SERVICE_DISABLED (Routes API not enabled),
                    // API_KEY_SERVICE_BLOCKED (key's API restrictions leave out Routes API),
                    // BILLING_DISABLED, API_KEY_HTTP_REFERRER_BLOCKED (browser-only key)
                    'reason' => collect($response->json('error.details') ?? [])->pluck('reason')->filter()->first(),
                ],
            ];

            return null;
        }

        $route = $response->json()['routes'][0] ?? null;

        if (!$route) {
            Log::warning('Google Routes API: no road route', compact('fromLat', 'fromLng', 'toLat', 'toLng'));
            $this->routeError = ['reason' => 'no_route'];
            return null;
        }

        return [
            'distance' => round(($route['distanceMeters'] ?? 0) / 1000, 2),
            'duration' => isset($route['duration'])
                ? round((int) rtrim($route['duration'], 's') / 60)
                : 0,
        ];
    }
}
