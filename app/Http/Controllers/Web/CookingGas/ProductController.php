<?php

namespace App\Http\Controllers\Web\CookingGas;

use App\Http\Controllers\Controller;
use App\Models\CookingGas\BusinessGasInventory;
use App\Models\CookingGas\GasQntImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display landing page with products from ACTIVE businesses with ECOMMERCE access
     */
    public function index()
    {
        $cylinders = BusinessGasInventory::query()
            ->select([
                'gas_cylinder_id',
                'gas_quantity_id',
                'business_id',
                DB::raw('MIN(refill_price) as min_price'),
                DB::raw('MAX(refill_price) as max_price'),
            ])
            ->whereHas('business', fn ($q) => $q->ecommerceEnabled())
            ->groupBy('gas_cylinder_id', 'gas_quantity_id', 'business_id')
            ->with([
                'cylinder:id,brand_name',
                'quantity:id,quantity',
                'business:id,name',
            ])
            ->get();

        $cylinders->each(function ($cylinder) {
            $cylinder->image = GasQntImage::where('gas_cylinder_id', $cylinder->gas_cylinder_id)
                ->where('quantity_id', $cylinder->gas_quantity_id)
                ->value('image_path');
        });

        // Pass 'cylinders' to the view
        return view('CookingGas.home', compact('cylinders'));
    }


    /**
     * Show specific cylinder details and other valid vendors
     */
    public function viewCylinder(Request $request)
    {
        $request->validate([
            'cylinder' => 'required|integer',
            'quantity' => 'required|integer',
            'business' => 'nullable|integer',
        ]);

        // =====================================================
        // BASE QUERY (STRICT SCOPE FOUNDATION)
        // =====================================================

        $baseQuery = BusinessGasInventory::query()
            ->where('gas_cylinder_id', $request->cylinder)
            ->where('gas_quantity_id', $request->quantity)
            ->whereHas('business', fn ($q) => $q->ecommerceEnabled())
            ->with([
                'cylinder:id,brand_name',
                'quantity:id,quantity',
                'business:id,name'
            ]);

        // =====================================================
        // APPLY BUSINESS FILTER (USER INTENT)
        // =====================================================

        if ($request->filled('business')) {
            $baseQuery->where('business_id', $request->business);
        }

        // =====================================================
        // TRY STRICT MATCH FIRST
        // =====================================================

        $product = (clone $baseQuery)->first();

        // =====================================================
        // FALLBACK (ONLY IF NO BUSINESS WAS SPECIFIED)
        // =====================================================

        if (!$product && !$request->filled('business')) {
            $product = BusinessGasInventory::query()
                ->where('gas_cylinder_id', $request->cylinder)
                ->where('gas_quantity_id', $request->quantity)
                ->whereHas('business', fn ($q) => $q->ecommerceEnabled())
                ->with([
                    'cylinder:id,brand_name',
                    'quantity:id,quantity',
                    'business:id,name'
                ])
                ->orderBy('refill_price')
                ->first();
        }

        // =====================================================
        // NOT FOUND
        // =====================================================

        if (!$product) {
            abort(404, 'Product not available for online purchase');
        }

        // Resolve final business context
        $resolvedBusinessId = $product->business_id;

        // =====================================================
        // MAIN PRODUCT IMAGE
        // =====================================================

        $product->image = GasQntImage::query()
            ->where('gas_cylinder_id', $product->gas_cylinder_id)
            ->where('quantity_id', $product->gas_quantity_id)
            ->value('image_path');

        // =====================================================
        // THUMBNAILS (STRICT SAME BUSINESS ONLY)
        // =====================================================

        $thumbnails = BusinessGasInventory::query()
            ->where('business_id', $resolvedBusinessId)
            ->where('gas_cylinder_id', $product->gas_cylinder_id)
            ->with([
                'quantity:id,quantity',
                'cylinder:id,brand_name',
                'business:id,name'
            ])
            ->orderBy('gas_quantity_id')
            ->get()
            ->map(function ($stock) {

                $stock->image = GasQntImage::query()
                    ->where('gas_cylinder_id', $stock->gas_cylinder_id)
                    ->where('quantity_id', $stock->gas_quantity_id)
                    ->value('image_path');

                $stock->route_url = route('gas.cylinder.view', [
                    'cylinder' => $stock->gas_cylinder_id,
                    'quantity' => $stock->gas_quantity_id,
                    'business' => $stock->business_id,
                ]);

                return $stock;
            });

        // =====================================================
        // OTHER VENDORS (SAME PRODUCT ONLY)
        // =====================================================

        $otherVendors = BusinessGasInventory::query()
            ->where('gas_cylinder_id', $product->gas_cylinder_id)
            ->where('gas_quantity_id', $product->gas_quantity_id)
            ->where('business_id', '!=', $resolvedBusinessId)
            ->whereHas('business', fn ($q) => $q->ecommerceEnabled())
            ->with([
                'business:id,name'
            ])
            ->orderBy('refill_price')
            ->get();

        // =====================================================
        // RETURN VIEW
        // =====================================================

        return view(
            'CookingGas.Products.viewCylinder',
            compact(
                'product',
                'otherVendors',
                'thumbnails'
            )
        );
    }


    /**
     * JSON Response for switching quantity variants
     */
    public function variant(Request $request)
    {
        $request->validate([
            'cylinder' => 'required|integer',
            'quantity' => 'required|integer',
        ]);

        $stocks = BusinessGasInventory::query()
            ->where('gas_cylinder_id', $request->cylinder)
            ->where('gas_quantity_id', $request->quantity)
            ->whereHas('business', fn ($q) => $q->ecommerceEnabled())
            ->with('business:id,name')
            ->orderBy('refill_price')
            ->get();

        if ($stocks->isEmpty()) {
            return response()->json(['price' => ['min' => '0', 'max' => '0'], 'vendors' => []]);
        }

        return response()->json([
            'price' => [
                'min' => number_format($stocks->first()->refill_price),
                'max' => number_format($stocks->last()->refill_price),
                'cheapest_business' => $stocks->first()->business->name,
                'cheapest_stock_id' => $stocks->first()->id,
            ],
            'vendors' => $stocks->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->business->name,
                'price' => number_format($v->refill_price),
            ]),
            'count' => $stocks->count(),
        ]);
    }

    public function search(Request $request)
    {
        $term = $request->get('term');
        $query = $this->getCylinderQuery();

        $query->when($term, function ($q) use ($term) {
            $q->whereHas('cylinder', fn($sub) => $sub->where('brand_name', 'LIKE', "{$term}%"));
        });

        // For a dropdown, get() is usually better than paginate()
        $cylinders = $request->ajax() ? $query->limit(10)->get() : $query->paginate(20);

        $this->attachImages($cylinders);

        if ($request->ajax()) {
            return view('CookingGas.partials.search', compact('cylinders'));
        }

        return view('CookingGas.home', compact('cylinders'));
    }

    /**
     * Shared Base Query to keep logic DRY and optimized
     */
    private function getCylinderQuery()
    {
        return BusinessGasInventory::query()
            ->select([
                'gas_cylinder_id',
                'gas_quantity_id',
                DB::raw('MIN(refill_price) as min_price'),
                DB::raw('MAX(refill_price) as max_price'),
            ])
            ->whereHas('business', fn($q) => $q->ecommerceEnabled())
            ->groupBy('gas_cylinder_id', 'gas_quantity_id')
            ->with(['cylinder:id,brand_name', 'quantity:id,quantity']);
    }

    /**
     * Batch process images to avoid N+1 database hits in loops
     */
    private function attachImages($collection)
    {
        $collection->each(function ($item) {
            $item->image = GasQntImage::where('gas_cylinder_id', $item->gas_cylinder_id)
                ->where('quantity_id', $item->gas_quantity_id)
                ->value('image_path');
        });
    }

}
