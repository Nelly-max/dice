<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CookingGas\GasQntImage;
use App\Models\CookingGas\BusinessGasStock;
use App\Models\SubDivision; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    // View cart
    public function view()
    {
        $userId = Auth::id() ?? 1;
        $cartItems = Cart::where('user_id', $userId)->get();
        
        // Prepare shipment info
        $shipmentInfo = [
            'quick' => ['icon' => 'ri-e-bike-2-line', 'label' => 'Quick in 50 - 120 Mins'],
            'standard' => ['icon' => 'ri-truck-line', 'label' => 'Standard delivery date and time'],
            'specific_shop' => ['icon' => 'ri-riding-line', 'label' => 'Order to specific shop']
        ];
        
        // Group items by shipment type
        $groupedItems = [];
        $shipmentTitles = ['Shipment 1', 'Shipment 2', 'Shipment 3'];
        $shipmentIndex = 0;
        
        foreach ($cartItems->groupBy('shipment_type') as $shipmentType => $items) {
            // Process each item for this shipment
            $processedItems = [];
            
            foreach ($items as $item) {
                // Get the product details and image
                $item = $this->getProductDetails($item);
                $processedItems[] = $item;
            }
            
            $groupedItems[$shipmentType] = [
                'items' => $processedItems,
                'title' => $shipmentTitles[$shipmentIndex] ?? 'Shipment',
                'info' => $shipmentInfo[$shipmentType] ?? ['icon' => 'ri-package-line', 'label' => 'Delivery'],
                'subtotal' => $items->sum(function($item) {
                    return $item->price * $item->quantity;
                })
            ];
            
            $shipmentIndex++;
            if ($shipmentIndex >= 3) break;
        }
        
        // Calculate grand total
        $total = $cartItems->sum(function($item) {
            return $item->price * $item->quantity;
        });

        return view('cart', compact('cartItems', 'groupedItems', 'total'));
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

    // Update quantity (form submission)
    public function update(Request $request)
    {
        $userId = Auth::id() ?? 1;

        $request->validate([
            'item_id' => 'required|integer',
            'action' => 'required|in:increase,decrease'
        ]);

        $item = Cart::where('user_id', $userId)
            ->where('id', $request->item_id)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
        }

        $qty = $item->quantity;

        if ($request->action === 'increase') {
            $qty = min(99, $qty + 1);
        }

        if ($request->action === 'decrease') {
            $qty = max(0, $qty - 1);
        }

        if ($qty === 0) {
            $item->delete();

            return response()->json([
                'success' => true,
                'deleted' => true
            ]);
        }

        $item->update(['quantity' => $qty]);

        return response()->json([
            'success' => true,
            'quantity' => $qty
        ]);
    }
    

    // Add to cart
    public function add(Request $request)
    {
        try {
            $request->validate([
                'stockable_id' => 'required|integer',
                'stockable_type' => 'required|string',
                'subdivision_code' => 'required|string',
                'quantity' => 'required|integer|min:1|max:99',
            ]);

            $userId = Auth::id() ?? 1;
            $sessionId = $request->session()->getId();
            
            // Check if product exists
            $productClass = $request->stockable_type;
            
            if ($productClass === 'App\\Models\\CookingGas\\BusinessGasStock') {
                $product = $productClass::on('cookinggas')
                    ->with(['business', 'gasCylinder', 'gasQuantity'])
                    ->find($request->stockable_id);
            } else {
                $product = $productClass::find($request->stockable_id);
            }
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            
            // Check if already in cart
            $existing = Cart::where(function($query) use ($userId, $sessionId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } else {
                        $query->where('session_id', $sessionId);
                    }
                })
                ->where('stockable_id', $request->stockable_id)
                ->where('stockable_type', $request->stockable_type)
                ->first();
            
            if ($existing) {
                $existing->quantity += $request->quantity;
                $existing->save();
            } else {
                // Get sub_division_id from hub if available
                $subDivision = null;
                if (class_exists(Subdivision::class)) {
                    $subDivision = Subdivision::on('hub')
                        ->where('name', 'like', '%' . $request->subdivision_code . '%')
                        ->orWhere('db_connection', $request->subdivision_code)
                        ->first();
                }
                
                // Get price
                $price = 0;
                if ($productClass === 'App\\Models\\CookingGas\\BusinessGasStock') {
                    $price = $product->refill_price ?? 0;
                }
                
                // Create new cart item
                Cart::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'subdivision_code' => $request->subdivision_code,
                    'sub_division_id' => $subDivision->id ?? null,
                    'stockable_id' => $request->stockable_id,
                    'stockable_type' => $request->stockable_type,
                    'quantity' => $request->quantity,
                    'price' => $price,
                    'shipment_type' => $request->shipment_type ?? 'quick',
                ]);
            }
            
            $cartCount = Cart::where(function($query) use ($userId, $sessionId) {
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