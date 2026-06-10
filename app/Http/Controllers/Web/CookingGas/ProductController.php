<?php

namespace App\Http\Controllers\Web\CookingGas;

use App\Http\Controllers\Controller;
use App\Models\CookingGas\BusinessGasStock;
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
        $cylinders = BusinessGasStock::query()
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
                ->value('file_path');
        });

        // Pass 'cylinders' to the view
        return view('CookingGas.home', compact('cylinders'));
    }


    /**
     * Show specific cylinder details and other valid vendors
     */
    public function show(Request $request)
    {
        $request->validate([
            'cylinder' => 'required|integer',
            'quantity' => 'required|integer',
            'business' => 'nullable|integer',
        ]);

        $allStockEntries = BusinessGasStock::query()
            ->where('gas_cylinder_id', $request->cylinder)
            ->where('gas_quantity_id', $request->quantity)
            ->whereHas('business', fn ($q) => $q->ecommerceEnabled())
            ->with(['cylinder:id,brand_name', 'quantity:id,quantity', 'business:id,name'])
            ->get();

        if ($allStockEntries->isEmpty()) {
            abort(404, 'Product not available for online purchase');
        }

        // Determine main product (specific business or cheapest)
        $product = $request->has('business') 
            ? $allStockEntries->where('business_id', $request->business)->first() 
            : $allStockEntries->sortBy('refill_price')->first();

        $otherVendors = $allStockEntries->where('business_id', '!=', $product->business_id);

        // Fetch available weight variants (thumbnails) from active vendors
        $thumbnails = BusinessGasStock::query()
            ->where('gas_cylinder_id', $product->gas_cylinder_id)
            ->whereHas('business', fn ($q) => $q->ecommerceEnabled())
            ->with(['quantity:id,quantity', 'cylinder:id,brand_name'])
            ->get()
            ->groupBy('gas_quantity_id')
            ->map(function ($stocks) {
                $cheapest = $stocks->sortBy('refill_price')->first();
                $cheapest->image = GasQntImage::where('gas_cylinder_id', $cheapest->gas_cylinder_id)
                    ->where('quantity_id', $cheapest->gas_quantity_id)
                    ->value('file_path');
                return $cheapest;
            })
            ->values();

        $product->image = GasQntImage::where('gas_cylinder_id', $product->gas_cylinder_id)
            ->where('quantity_id', $product->gas_quantity_id)
            ->value('file_path');

        return view('CookingGas.Products.viewCylinder', compact('product', 'otherVendors', 'thumbnails'));
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

        $stocks = BusinessGasStock::query()
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
        return BusinessGasStock::query()
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
                ->value('file_path');
        });
    }




}
