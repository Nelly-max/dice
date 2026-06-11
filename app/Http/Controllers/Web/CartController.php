<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\ItemReservation;
use App\Models\CookingGas\GasQntImage;
use App\Models\CookingGas\BusinessGasStock;
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
            ->with([
                'stockable',
                'subdivision.shipment'
            ])
            ->get();

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
        $total = collect($formattedShipments)->sum('subtotal');

        return view('cart', [
            'cartItems' => $cartItems,
            'shipments' => $formattedShipments,
            'total' => $total
        ]);
    }

    // Get product details including image
    private function getProductDetails($cartItem)
    {
        // Default values
        $cartItem->product_name = 'Product';
        $cartItem->business_name = null;
        $cartItem->image = null;
        
        // If it's a cooking gas product
        if ($cartItem->stockable_type === 'App\\Models\\CookingGas\\BusinessGasStock') {
            $product = BusinessGasStock::on('cookinggas')
                ->with(['business', 'gasCylinder', 'gasQuantity'])
                ->find($cartItem->stockable_id);
            
            if ($product) {
                // Set product name
                $cartItem->product_name = ($product->gasCylinder->brand_name ?? 'Gas Cylinder');
                if ($product->gasQuantity) {
                    $cartItem->product_name .= ' - ' . $product->gasQuantity->quantity;
                }
                
                // Set business name
                $cartItem->business_name = $product->business->name ?? null;
                
                // Set price
                $cartItem->price = $product->refill_price ?? $cartItem->price;
                
                // Get image - EXACTLY like in ProductController
                $cartItem->image = GasQntImage::where('gas_cylinder_id', $product->gas_cylinder_id)
                    ->where('quantity_id', $product->gas_quantity_id)
                    ->value('file_path');
            }
        }
        
        return $cartItem;
    }

    private function getAvailableStock($stockable): int
    {
        if (method_exists($stockable, 'availableStock')) {
            return (int) $stockable->availableStock();
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

            $reservedByOthers = ItemReservation::query()
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
        try {
            $request->validate([
                'stockable_id' => 'required|integer',
                'stockable_type' => 'required|string', // registry key
                'business_account' => 'nullable|string',
                'subdivision_code' => 'required|string',
                'quantity' => 'required|integer|min:1|max:99',
                'shipment_type' => 'nullable|string',
            ]);

            $userId = Auth::id();
            $sessionId = $request->session()->getId();

            // =====================================================
            // 1. RESOLVE MODEL FROM REGISTRY
            // =====================================================
            $map = config('stockables');

            $typeKey = $request->stockable_type;

            if (!isset($map[$typeKey])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid stockable type'
                ], 400);
            }

            $productClass = $map[$typeKey];

            // =====================================================
            // 2. ENSURE MODEL CONTRACT EXISTS
            // =====================================================
            if (!method_exists($productClass, 'resolveById')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model missing resolveById method'
                ], 500);
            }

            $product = $productClass::resolveById($request->stockable_id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // =====================================================
            // 3. BUSINESS ACCOUNT RESOLUTION (ROBUST)
            // =====================================================
            $businessAccount = null;

            if (!empty($request->business_account)) {
                $businessAccount = $request->business_account;
            }

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

            // =====================================================
            // 4. SUBDIVISION RESOLUTION (FIXED via db_connection)
            // =====================================================
            $subDivisionId = null;

            if (!empty($request->subdivision_code)) {

                $code = strtolower(str_replace('_', '', $request->subdivision_code));

                $subDivision = DB::table('sub_divisions')
                    ->where('db_connection', $code)
                    ->first();

                if (!$subDivision) {
                    $subDivision = DB::table('sub_divisions')
                        ->whereRaw('LOWER(name) = ?', [
                            str_replace('_', ' ', $request->subdivision_code)
                        ])
                        ->first();
                }

                $subDivisionId = $subDivision->id ?? null;
            }

            // =====================================================
            // 5. CHECK EXISTING CART ITEM
            // =====================================================
            $existing = Cart::where(function ($query) use ($userId, $sessionId) {
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

            if ($existing) {
                $existing->quantity += $request->quantity;
                $existing->save();
            } else {

                // =================================================
                // 6. CREATE CART ITEM
                // =================================================
                Cart::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,

                    'business_account' => $businessAccount,

                    'subdivision_code' => $request->subdivision_code,
                    'sub_division_id' => $subDivisionId,

                    'stockable_id' => $request->stockable_id,
                    'stockable_type' => $typeKey,

                    'quantity' => $request->quantity,
                    'shipment_type' => $request->shipment_type ?? 'quick',
                ]);
            }

            // =====================================================
            // 7. CART COUNT
            // =====================================================
            $cartCount = Cart::where(function ($query) use ($userId, $sessionId) {
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
                'cart_count' => $cartCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    // Remove item
    public function remove($id)
    {
        $userId = Auth::id() ?? 1;
        Cart::where('user_id', $userId)->where('id', $id)->delete();
        
        return redirect()->route('cart.view')->with('success', 'Item removed from cart!');
    }

    // Clear cart
    public function clear()
    {
        $userId = Auth::id() ?? 1;
        Cart::where('user_id', $userId)->delete();
        
        return redirect()->route('cart.view')->with('success', 'Cart cleared!');
    }
}