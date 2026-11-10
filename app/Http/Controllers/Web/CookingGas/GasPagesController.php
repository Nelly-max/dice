<?php

namespace App\Http\Controllers\Web\CookingGas;

use App\Http\Controllers\Controller;
use App\Models\CookingGas\Business;
use App\Models\CookingGas\BusinessGasInventory;
use App\Models\CookingGas\GasCylinder;
use App\Models\CookingGas\GasQntImage;
use App\Models\CookingGas\GasQuantity;
use App\Services\DeliveryLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cooking Gas browse pages (header strip: Gas cylinders · Brands · Shops near you).
 *
 *   /gas/cylinders           every refill, one card per shop (filter ?brand= ?size=, ?sort=)
 *   /gas/brands              the gas brands with their sizes and lowest price
 *   /gas/shops               gas shops, nearest to the saved delivery location first
 *   /gas/shops/{business}    one shop's refills
 *
 * Same rules as Home Market (App\Services\CookingGas\GasCatalog): subscribed shops with
 * e-commerce, open, in stock, the cart's gas shop or within the delivery radius.
 * Cards are CookingGas.Products.cylinders.
 */
class GasPagesController extends Controller
{
    public function __construct(private DeliveryLocationService $location)
    {
    }

    public const SORTS = [
        'near' => 'Nearest shop',
        'price' => 'Price: low to high',
        'price-hi' => 'Price: high to low',
        'brand' => 'Brand A–Z',
    ];

    public function cylinders(Request $request)
    {
        $loc = $this->location->get($request);
        $rows = $this->withDistance($this->stock()->get(), $loc);

        $brandId = (int) $request->query('brand', 0);
        $sizeId = (int) $request->query('size', 0);

        $brands = $rows->pluck('cylinder')->filter()->unique('id')->sortBy('brand_name')->values();
        $sizes = $rows->pluck('quantity')->filter()->unique('id')->sortBy(fn ($q) => (float) $q->quantity)->values();

        if ($brandId) $rows = $rows->where('gas_cylinder_id', $brandId);
        if ($sizeId) $rows = $rows->where('gas_quantity_id', $sizeId);

        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : ($loc ? 'near' : 'price');
        $rows = $this->sorted($rows, $sort);

        $brand = $brandId ? $brands->firstWhere('id', $brandId) : null;

        return view('CookingGas.pages.list', [
            'kind' => 'cylinders',
            'title' => $brand ? $brand->brand_name . ' gas' : 'Gas cylinders',
            'intro' => $brand
                ? 'Every ' . $brand->brand_name . ' refill from gas shops on Smart Market.'
                : 'Every refill from gas shops on Smart Market' . ($loc ? ', nearest shop first.' : '.'),
            'icon' => 'fa-solid fa-fire-flame-simple',
            'cylinders' => $this->withImages($rows->values()),
            'brands' => $brands,
            'sizes' => $sizes,
            'brandId' => $brandId,
            'sizeId' => $sizeId,
            'sort' => $sort,
            'sorts' => $loc ? self::SORTS : array_diff_key(self::SORTS, ['near' => 1]),
            'hasLocation' => (bool) $loc,
            'shop' => null,
        ]);
    }

    public function brands(Request $request)
    {
        $rows = $this->withImages($this->stock()->get());

        $brands = $rows->groupBy('gas_cylinder_id')->map(function (Collection $items) {
            $first = $items->first();
            $img = $items->firstWhere('image')?->image;

            return (object) [
                'id' => $first->gas_cylinder_id,
                'name' => $first->cylinder->brand_name ?? 'Gas',
                'image' => $img ? rtrim(config('app.media_url'), '/') . '/' . ltrim($img, '/') : null,
                'sizes' => $items->pluck('quantity')->filter()->unique('id')->sortBy(fn ($q) => (float) $q->quantity)
                    ->map(fn ($q) => is_numeric($q->quantity) ? $q->quantity . ' kg' : $q->quantity)->values(),
                'shops' => $items->pluck('business_id')->unique()->count(),
                'from' => (float) $items->min('min_price'),
                'url' => route('gas.cylinders', ['brand' => $first->gas_cylinder_id]),
            ];
        })->sortBy('name')->values();

        return view('CookingGas.pages.brands', compact('brands'));
    }

    public function shops(Request $request)
    {
        $loc = $this->location->get($request);
        $rows = $this->stock()->get();
        $radius = (int) config('homemarket.max_radius_m', 3000);

        $shops = $rows->groupBy('business_id')->map(function (Collection $items) use ($loc, $radius) {
            $b = $items->first()->business;
            $m = $loc ? $this->metres($loc, $b) : null;

            return (object) [
                'id' => $b->id,
                'name' => $b->name,
                'initial' => mb_strtoupper(mb_substr(trim((string) $b->name) ?: 'G', 0, 1)),
                'open' => ($b->shop_status ?? 'open') === 'open',
                'distance_m' => $m,
                'delivers' => $m !== null && $m <= $radius,
                'brands' => $items->pluck('cylinder.brand_name')->filter()->unique()->sort()->values(),
                'refills' => $items->count(),
                'from' => (float) $items->min('min_price'),
                'url' => route('gas.shop', $b->id),
            ];
        });

        $shops = $loc
            ? $shops->sortBy(fn ($s) => $s->distance_m ?? PHP_INT_MAX)->values()
            : $shops->sortBy('name')->values();

        return view('CookingGas.pages.shops', [
            'shops' => $shops,
            'hasLocation' => (bool) $loc,
            'radiusKm' => $radius / 1000,
            'outOfRange' => app(\App\Services\CookingGas\GasCatalog::class)->context()['outOfRange'],
        ]);
    }

    public function shop(Request $request, int $business)
    {
        // only a shop this buyer may see (GasCatalog rules)
        abort_unless(in_array($business, app(\App\Services\CookingGas\GasCatalog::class)->visibleIds(), true), 404);
        $shop = Business::query()->findOrFail($business);
        $loc = $this->location->get($request);

        $rows = $this->stock()->where('business_id', $shop->id)->get();
        $sizeId = (int) $request->query('size', 0);
        $sizes = $rows->pluck('quantity')->filter()->unique('id')->sortBy(fn ($q) => (float) $q->quantity)->values();
        $brands = $rows->pluck('cylinder')->filter()->unique('id')->sortBy('brand_name')->values();
        $brandId = (int) $request->query('brand', 0);

        if ($brandId) $rows = $rows->where('gas_cylinder_id', $brandId);
        if ($sizeId) $rows = $rows->where('gas_quantity_id', $sizeId);

        $sort = in_array($request->query('sort'), ['price', 'price-hi', 'brand'], true) ? $request->query('sort') : 'brand';
        $m = $loc ? $this->metres($loc, $shop) : null;

        return view('CookingGas.pages.list', [
            'kind' => 'shop',
            'title' => $shop->name,
            'intro' => $m !== null
                ? ($m < 100 ? 'Under 100 m' : number_format($m / 1000, 1) . ' km') . ' from your delivery location.'
                : 'Gas refills from this shop.',
            'icon' => 'fa-solid fa-store',
            'cylinders' => $this->withImages($this->sorted($rows, $sort)->values()),
            'brands' => $brands,
            'sizes' => $sizes,
            'brandId' => $brandId,
            'sizeId' => $sizeId,
            'sort' => $sort,
            'sorts' => array_diff_key(self::SORTS, ['near' => 1]),
            'hasLocation' => (bool) $loc,
            'shop' => $shop,
        ]);
    }

    /**
     * "Shops near you" on the gas home page: open gas shops inside the delivery radius of
     * the saved location, nearest first. Same shape as Home Market's (ShopDiscovery::nearbyShops):
     * id, name, distance_m, distance_km, eta_min, initial, url.
     */
    public function nearbyShops(?array $loc, int $limit = 8): Collection
    {
        if (!$loc) return collect();

        $gas = app(\App\Services\CookingGas\GasCatalog::class)->context();

        // in-range shops that have refills in stock
        $withStock = BusinessGasInventory::query()->sellable()->distinct()->pluck('business_id')->map(fn ($id) => (int) $id)->all();

        return $gas['inRange']
            ->filter(fn ($s) => in_array((int) $s->id, $withStock, true))
            ->take($limit)
            ->map(function ($s) {
                $km = round($s->distance_m / 1000, 1);

                return (object) [
                    'id' => $s->id,
                    'name' => $s->name,
                    'distance_m' => $s->distance_m,
                    'distance_km' => $km,
                    'eta_min' => (int) (15 + ceil($km * 6)),
                    'initial' => mb_strtoupper(mb_substr(trim((string) $s->name) ?: 'G', 0, 1)),
                    'url' => route('gas.shop', $s->id),
                ];
            })
            ->values();
    }

    /* ------------------------------------------------------------------ */

    /** One row per shop + brand + size, with the stock row id so "Order" works. */
    private function stock()
    {
        return BusinessGasInventory::query()
            ->select([
                'gas_cylinder_id',
                'gas_quantity_id',
                'business_id',
                DB::raw('MIN(id) as id'),
                DB::raw('MIN(refill_price) as min_price'),
                DB::raw('MAX(refill_price) as max_price'),
            ])
            ->sellable()   // Home Market rules: GasCatalog
            ->groupBy('gas_cylinder_id', 'gas_quantity_id', 'business_id')
            ->with([
                'cylinder:id,brand_name',
                'quantity:id,quantity',
                'business:id,name,account,latitude,longitude,shop_status',
            ]);
    }

    private function withImages(Collection $rows): Collection
    {
        if ($rows->isEmpty()) return $rows;

        $images = GasQntImage::query()
            ->whereIn('gas_cylinder_id', $rows->pluck('gas_cylinder_id')->unique())
            ->get(['gas_cylinder_id', 'quantity_id', 'image_path'])
            ->groupBy(fn ($i) => $i->gas_cylinder_id . ':' . $i->quantity_id);

        return $rows->each(function ($row) use ($images) {
            $row->image = optional($images->get($row->gas_cylinder_id . ':' . $row->gas_quantity_id)?->first())->image_path;
        });
    }

    private function withDistance(Collection $rows, ?array $loc): Collection
    {
        return $rows->each(function ($row) use ($loc) {
            $row->distance_m = $loc && $row->business ? $this->metres($loc, $row->business) : null;
        });
    }

    private function sorted(Collection $rows, string $sort): Collection
    {
        return match ($sort) {
            'near' => $rows->sortBy([fn ($a, $b) => ($a->distance_m ?? PHP_INT_MAX) <=> ($b->distance_m ?? PHP_INT_MAX), fn ($a, $b) => $a->min_price <=> $b->min_price]),
            'price-hi' => $rows->sortByDesc('min_price'),
            'brand' => $rows->sortBy([fn ($a, $b) => strcasecmp($a->cylinder->brand_name ?? '', $b->cylinder->brand_name ?? ''), fn ($a, $b) => (float) ($a->quantity->quantity ?? 0) <=> (float) ($b->quantity->quantity ?? 0)]),
            default => $rows->sortBy('min_price'),
        };
    }

    /** Straight-line metres from the delivery location to a shop (null when the shop has no pin). */
    private function metres(array $loc, $business): ?int
    {
        if (!is_numeric($business->latitude ?? null) || !is_numeric($business->longitude ?? null)) return null;

        $r = 6371000;
        $dLat = deg2rad($business->latitude - $loc['latitude']);
        $dLng = deg2rad($business->longitude - $loc['longitude']);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($loc['latitude'])) * cos(deg2rad($business->latitude)) * sin($dLng / 2) ** 2;

        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
