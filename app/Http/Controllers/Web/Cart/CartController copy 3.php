<?php

namespace App\Http\Controllers\Web\Cart;

use App\Http\Controllers\Controller;
use App\Models\Customer\Cart;
use App\Models\Customer\ItemReservation;
use App\Models\CookingGas\BusinessGasInventory;
use App\Models\HomeMarket\RetailInventory;
use App\Models\SubDivision; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // View cart
    public function view()
    {
        $cartItems = Cart::forCurrent()
            ->with(['stockable','subdivision.shipment'])
            ->get();

        foreach ($cartItems as $item) {

            $availableStock = $this->getAvailableStock($item->stockable);

            $reservedByOthers = ItemReservation::active()
                ->where('stockable_type', $item->stockable_type)
                ->where('stockable_id', $item->stockable_id)
                ->where('cart_id', '!=', $item->id)
                ->where('expires_at', '>', now())
                ->sum('quantity');

            $available = max(0, $availableStock - $reservedByOthers);

            // If cart has more than available stock → adjust it
            if ($item->quantity > $available) {

                if ($available == 0) {
                    $item->delete(); // remove from cart completely
                } else {
                    $item->update([
                        'quantity' => $available
                    ]);
                }
            }
        }

        // =====================================================
        // SHIPMENT TYPES
        // =====================================================
        $shipmentTypes = DB::table('shipment_types')
            ->get()
            ->keyBy('id');

        $shipmentInfo = [
            'quick' => [
                'icon' => 'ri-e-bike-2-line',
                'label' => 'Quick in 10 - 50 Mins'
            ],
            'standard' => [
                'icon' => 'ri-truck-line',
                'label' => 'Standard delivery'
            ],
            'specific_shop' => [
                'icon' => 'ri-riding-line',
                'label' => 'Order to specific shop'
            ]
        ];

        // =====================================================
        // GROUP BY SHIPMENT TYPE
        // =====================================================
        $groupedByType = $cartItems->groupBy('shipment_type_id');

        $shipments = [];

        foreach ($groupedByType as $typeId => $items) {

            $typeRule = $shipmentTypes->get($typeId);

            $typeName = strtolower($typeRule->name ?? 'standard');

            $minAmount = $typeRule->min_amount ?? 0;

            $timeRange = [
                'from' => $typeRule->time1 ?? null,
                'to'   => $typeRule->time2 ?? null,
            ];

            $groupItems = collect();
            $singleItems = collect();

            // =================================================
            // SPLIT ITEMS BY CONSIGNMENT
            // =================================================
            foreach ($items as $item) {

                $consignment = strtolower(
                    $item->subdivision?->shipment?->consignment ?? 'group'
                );

                if ($consignment === 'single') {
                    $singleItems->push($item);
                } else {
                    $groupItems->push($item);
                }
            }

            // =================================================
            // GROUP ITEMS
            // Same shipment type + group consignment
            // => ONE shipment
            // =================================================
            if ($groupItems->isNotEmpty()) {

                $shipments["group_{$typeId}"] = [
                    'shipment_type_id' => $typeId,
                    'shipment_type' => $typeName,
                    'mode' => 'group',

                    'subdivision' => null,

                    'min_amount' => $minAmount,
                    'time_range' => $timeRange,

                    'items' => $groupItems,
                ];
            }

            // =================================================
            // SINGLE ITEMS
            // Each item becomes its own shipment
            // =================================================
            foreach ($singleItems as $item) {

                $shipments["single_{$typeId}_{$item->id}"] = [
                    'shipment_type_id' => $typeId,
                    'shipment_type' => $typeName,
                    'mode' => 'single',

                    'subdivision' => $item->subdivision,

                    'min_amount' => $minAmount,
                    'time_range' => $timeRange,

                    'items' => collect([$item]),
                ];
            }
        }

        // =====================================================
        // FORMAT SHIPMENTS
        // =====================================================
        $formattedShipments = [];
        $shipmentNumber = 1;

        foreach ($shipments as $shipment) {

            $items = $shipment['items'];

            $subtotal = $items->sum(function ($item) {
                return ($item->price ?? 0) * $item->quantity;
            });

            $typeName = $shipment['shipment_type'];

            $formattedShipments[] = [
                'number' => $shipmentNumber,
                'title' => 'Shipment ' . $shipmentNumber,

                'shipment_type' => $typeName,
                'shipment_type_id' => $shipment['shipment_type_id'],

                'info' => $shipmentInfo[$typeName] ?? [
                    'icon' => 'ri-package-line',
                    'label' => ucfirst($typeName)
                ],

                'mode' => $shipment['mode'],
                'subdivision' => $shipment['subdivision'],

                'min_amount' => $shipment['min_amount'],
                'time_range' => $shipment['time_range'],

                'items' => $items,

                'item_count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),

                'subtotal' => $subtotal,
            ];

            $shipmentNumber++;
        }

        // =====================================================
        // TOTAL
        // =====================================================
        $expired = false;

        if ($cartItems->isEmpty()) {

            $expired = Cart::where(function ($query) {

                    if (auth('customer')->check()) {
                        $query->where('user_id', auth('customer')->id());
                    } else {
                        $query->where(
                            'session_id',
                            session()->getId()
                        );
                    }

                })
                ->exists();
        }

        // =====================================================
        // TOTAL
        // =====================================================
        $total = collect($formattedShipments)->sum('subtotal');

        return view('Cart.cart', [
            'cartItems' => $cartItems,
            'shipments' => $formattedShipments,
            'total' => $total,
            'expired'    => $expired
        ]);
    }

    private function getAvailableStock($stockable): int
    {
        if (!$stockable) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Cooking Gas
        |--------------------------------------------------------------------------
        */
        if ($stockable instanceof BusinessGasInventory) {
            return (int) ($stockable->filled_cylinders ?? 0);
        }

        /*
        |--------------------------------------------------------------------------
        | Home Market
        |--------------------------------------------------------------------------
        */
        if ($stockable instanceof RetailInventory) {
            return (int) ($stockable->stock_available ?? 0);
        }

        /*
        |--------------------------------------------------------------------------
        | Generic Fallback
        |--------------------------------------------------------------------------
        */
        if (isset($stockable->stock_available)) {
            return (int) $stockable->stock_available;
        }

        return 0;
    }

    // Update quantity (form submission)
    public function update(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'action'  => 'required|in:increase,decrease',
        ]);

        $item = Cart::forCurrent()
            ->with('stockable')
            ->where('id', $request->item_id)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        DB::beginTransaction();

        try {

            $quantity = $item->quantity;

            if ($request->action === 'increase') {

                $reservedByOthers = ItemReservation::active()
                    ->where('stockable_type', $item->stockable_type)
                    ->where('stockable_id', $item->stockable_id)
                    ->where('cart_id', '!=', $item->id)
                    ->where('expires_at', '>', now())
                    ->sum('quantity');

                $availableStock = $this->getAvailableStock($item->stockable);

                $available = max(
                    0,
                    $availableStock - $reservedByOthers
                );

                if (($quantity + 1) > $available) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => 'Only ' . $available . ' item(s) available.'
                    ], 422);
                }

                $quantity++;
            }

            if ($request->action === 'decrease') {
                $quantity--;
            }

            /*
            |--------------------------------------------------------------------------
            | DELETE ITEM
            |--------------------------------------------------------------------------
            */

            if ($quantity <= 0) {

                ItemReservation::where(
                    'cart_id',
                    $item->id
                )->delete();

                $item->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'deleted' => true,
                    'cart_count' => Cart::forCurrent()->sum('quantity')
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE CART
            |--------------------------------------------------------------------------
            */

            $item->update([
                'quantity' => $quantity
            ]);

            /*
            |--------------------------------------------------------------------------
            | UPDATE RESERVATION
            |--------------------------------------------------------------------------
            */

            ItemReservation::updateOrCreate(
                [
                    'cart_id' => $item->id,
                ],
                [
                    'stockable_type' => $item->stockable_type,
                    'stockable_id'   => $item->stockable_id,
                    'quantity'       => $quantity,
                    'expires_at'     => now()->addMinutes(10),

                    'updated_at'     => now(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'quantity' => $quantity,
                'cart_count' => Cart::forCurrent()->sum('quantity'),
                'expires_at' => now()->addMinutes(10)->toDateTimeString(),
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to update cart.'
            ], 500);
        }
    }

    // Add to cart
    public function add(Request $request)
    {
        DB::beginTransaction();

        try {

            $request->validate([
                'stockable_id'     => 'required|integer',
                'stockable_type'   => 'required|string',
                'business_account' => 'nullable|string',
                'subdivision_code' => 'required|string',
                'quantity'         => 'required|integer|min:1|max:99',
                'shipment_type'    => 'nullable|string',
            ]);

            $userId = auth('customer')->id();
            $sessionId = $request->session()->getId();

            /*
            |--------------------------------------------------------------------------
            | Resolve Stockable Class
            |--------------------------------------------------------------------------
            */

            $map = config('stockables');

            $typeKey = $request->stockable_type;

            if (!isset($map[$typeKey])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid stockable type'
                ], 400);
            }

            $productClass = $map[$typeKey];

            if (!method_exists($productClass, 'resolveById')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model missing resolveById method'
                ], 500);
            }

            $product = $productClass::resolveById(
                $request->stockable_id
            );

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Business Account
            |--------------------------------------------------------------------------
            */

            $businessAccount = $request->business_account;

            if (!$businessAccount) {

                if (method_exists($product, 'getBusinessAccount')) {
                    $businessAccount = $product->getBusinessAccount();
                }

                if (!$businessAccount && isset($product->business_account)) {
                    $businessAccount = $product->business_account;
                }

                if (!$businessAccount && isset($product->business)) {
                    $businessAccount =
                        $product->business->account
                        ?? $product->business->business_account
                        ?? null;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Sub Division
            |--------------------------------------------------------------------------
            */

            $subDivisionId = null;

            $code = strtolower(
                str_replace('_', '', $request->subdivision_code)
            );

            $subDivision = DB::table('sub_divisions')
                ->where('db_connection', $code)
                ->first();

            if (!$subDivision) {

                $subDivision = DB::table('sub_divisions')
                    ->whereRaw(
                        'LOWER(name) = ?',
                        [str_replace('_', ' ', $request->subdivision_code)]
                    )
                    ->first();
            }

            $subDivisionId = $subDivision->id ?? null;

            /*
            |--------------------------------------------------------------------------
            | Stock Validation
            |--------------------------------------------------------------------------
            */

            $availableStock = $this->getAvailableStock($product);

            $reserved = ItemReservation::query()
                ->where('stockable_type', $typeKey)
                ->where('stockable_id', $request->stockable_id)
                ->where('expires_at', '>', now())
                ->sum('quantity');

            $available = max(
                0,
                $availableStock - $reserved
            );

            if ($request->quantity > $available) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "Only {$available} item(s) available."
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Existing Cart Item
            |--------------------------------------------------------------------------
            */

            $existing = Cart::active()
                ->where(function ($query) use ($userId, $sessionId) {

                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }

                })
                ->where('stockable_id', $request->stockable_id)
                ->where('stockable_type', $typeKey)
                ->where('business_account', $businessAccount)
                ->first();
            /*
            |--------------------------------------------------------------------------
            | Update Existing Cart
            |--------------------------------------------------------------------------
            */

            if ($existing) {

                $reservedByOthers = ItemReservation::active()
                    ->where('stockable_type', $typeKey)
                    ->where('stockable_id', $request->stockable_id)
                    ->where('cart_id', '!=', $existing->id)
                    ->where('expires_at', '>', now())
                    ->sum('quantity');

                $availableForThisCart = max(
                    0,
                    $availableStock - $reservedByOthers
                );

                $newQuantity =
                    $existing->quantity + $request->quantity;

                if ($newQuantity > $availableForThisCart) {

                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => "Only {$availableForThisCart} item(s) available."
                    ], 422);
                }

                $existing->update([
                    'quantity' => $newQuantity
                ]);

                $cart = $existing;

            } else {

                /*
                |--------------------------------------------------------------------------
                | Create Cart Item
                |--------------------------------------------------------------------------
                */

                $cart = Cart::create([
                    'user_id'          => $userId,
                    'session_id'       => $sessionId,
                    'business_account' => $businessAccount,

                    'subdivision_code' => $request->subdivision_code,
                    'sub_division_id'  => $subDivisionId,

                    'stockable_id'     => $request->stockable_id,
                    'stockable_type'   => $typeKey,

                    'quantity'         => $request->quantity,

                    'shipment_type'    => $request->shipment_type ?? 'quick',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Reservation
            |--------------------------------------------------------------------------
            */

            ItemReservation::updateOrCreate(
                [
                    'cart_id' => $cart->id,
                ],
                [
                    'stockable_type' => $cart->stockable_type,
                    'stockable_id'   => $cart->stockable_id,
                    'quantity'       => $cart->quantity,
                    'expires_at'     => now()->addMinutes(10),

                    'updated_at'     => now(),
                ]
            );

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Cart Count
            |--------------------------------------------------------------------------
            */

            $cartCount = Cart::active()
                ->where(function ($query) use ($userId, $sessionId) {

                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }

                })
                ->sum('quantity');

            return response()->json([
                'success' => true,
                'message' => 'Added to cart!',
                'cart_count' => $cartCount,
                'expires_at' => now()->addMinutes(10)->toDateTimeString(),
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}