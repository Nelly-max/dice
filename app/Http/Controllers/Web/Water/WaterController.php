<?php

namespace App\Http\Controllers\Web\Water;

use App\Http\Controllers\Controller;
use App\Models\Water\Business;
use App\Models\Water\Inventory;
use App\Services\DeliveryLocationService;
use App\Services\Water\WaterCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Water (water database). Header strip: Home · Purified water refill · Bottled water ·
 * Clean water · Shops near you.
 *
 *   /water                   home: shops near you, a row per option; ?q= searches
 *   /water/refill            purified water refills   (inventory.refill_price)
 *   /water/bottled           bottled water            (inventory.sale_price)
 *   /water/clean-water       clean water by truck for estates (truck-sized containers)
 *   /water/shops             water shops, nearest first
 *   /water/shops/{business}  one shop's price list
 *   /water/product/{id}      one container from one shop, with its options
 *
 * Same rules as Home Market (App\Services\Water\WaterCatalog): subscribed shops with
 * e-commerce, open, the cart's water shop or within the delivery radius.
 * Each card is one "offer": an inventory row + option (refill | bottled | truck); the option
 * is the cart's stockable type (water_refill | water_bottled | water_truck).
 */
class WaterController extends Controller
{
    public const SORTS = [
        'near' => 'Nearest shop',
        'price' => 'Price: low to high',
        'price-hi' => 'Price: high to low',
        'size' => 'Size',
    ];

    public function __construct(private DeliveryLocationService $location, private WaterCatalog $catalog)
    {
    }

    public function index(Request $request)
    {
        $loc = $this->location->get($request);
        $ctx = $this->catalog->context();
        $q = trim((string) $request->query('q', ''));
        $offers = $this->offers($loc);

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $results = $offers->filter(fn ($o) => str_contains(mb_strtolower($o->name . ' ' . $o->brand . ' ' . $o->shop . ' ' . $o->size . ' ' . $this->options()[$o->option]['label']), $needle));

            return view('Water.home', [
                'q' => $q,
                'filtering' => true,
                'results' => $this->sorted($results, $loc ? 'near' : 'price')->values(),
                'hasLocation' => (bool) $loc,
            ]);
        }

        $sort = $loc ? 'near' : 'price';

        return view('Water.home', [
            'q' => '',
            'filtering' => false,
            'rows' => collect($this->options())->map(fn ($meta, $key) => [
                'meta' => $meta,
                'items' => $this->sorted($offers->where('option', $key), $sort)->take(16)->values(),
            ]),
            'hasLocation' => (bool) $loc,
            'outOfRange' => $ctx['outOfRange'],
            'cartShopName' => $ctx['cartShop']->name ?? null,
            'nearbyShops' => $this->nearbyShops($loc),
            'radiusKm' => $ctx['radius'] / 1000,
        ]);
    }

    public function refill(Request $request)
    {
        return $this->list($request, 'refill');
    }

    public function bottled(Request $request)
    {
        return $this->list($request, 'bottled');
    }

    public function clean(Request $request)
    {
        return $this->list($request, 'truck');
    }

    public function shops(Request $request)
    {
        $loc = $this->location->get($request);
        $ctx = $this->catalog->context();
        $radius = $ctx['radius'];
        $offers = $this->offers($loc);

        $shops = $offers->groupBy('shop_account')->map(function (Collection $items) use ($radius) {
            $first = $items->first();
            $m = $first->distance_m;

            return (object) [
                'id' => $first->shop_id,
                'name' => $first->shop,
                'initial' => mb_strtoupper(mb_substr(trim((string) $first->shop) ?: 'W', 0, 1)),
                'open' => true,
                'distance_m' => $m,
                'delivers' => $m !== null && $m <= $radius,
                'sells' => $items->pluck('option')->unique()->map(fn ($o) => $this->options()[$o]['short'])->values(),
                'from' => (float) $items->min('price'),
                'url' => route('water.shop', $first->shop_id),
            ];
        });

        $shops = $loc
            ? $shops->sortBy(fn ($s) => $s->distance_m ?? PHP_INT_MAX)->values()
            : $shops->sortBy('name')->values();

        return view('Water.pages.shops', [
            'shops' => $shops,
            'hasLocation' => (bool) $loc,
            'radiusKm' => $radius / 1000,
            'outOfRange' => $ctx['outOfRange'],
        ]);
    }

    public function shop(Request $request, int $business)
    {
        $shop = Business::query()->find($business);
        // only a shop this buyer may see (WaterCatalog rules)
        abort_unless($shop && in_array((string) $shop->account, $this->catalog->visibleAccounts(), true), 404);

        $loc = $this->location->get($request);
        $m = $loc ? $this->catalog->metres($loc, $shop) : null;

        return $this->list($request, null, $shop, $m !== null
            ? ($m < 100 ? 'Under 100 m' : number_format($m / 1000, 1) . ' km') . ' from your delivery location.'
            : 'Water from this shop.');
    }

    public function show(Request $request, int $id)
    {
        $row = Inventory::query()->sellable()->with(['container', 'business'])->find($id);
        abort_unless($row && $row->business, 404);

        $loc = $this->location->get($request);
        $offers = $this->offersFor($row, $loc);
        abort_if($offers->isEmpty(), 404);

        $all = $this->offers($loc);
        $sameShop = $all->where('shop_account', $row->business_account)->where('id', '!=', $row->id)->values();
        $sameSize = $all->where('size', $offers->first()->size)->where('shop_account', '!=', $row->business_account)->values();

        return view('Water.product', [
            'row' => $row,
            'offers' => $offers,
            'first' => $offers->first(),
            'distance' => $offers->first()->distance_m,
            'sameShop' => $this->sorted($sameShop, 'size')->take(16)->values(),
            'sameSize' => $this->sorted($sameSize, $loc ? 'near' : 'price')->take(16)->values(),
            'options' => $this->options(),
        ]);
    }

    /**
     * "Shops near you" on the water home page: in-range shops with something to sell, nearest first.
     * Same shape as Home Market's: id, name, distance_m, distance_km, eta_min, initial, url.
     */
    public function nearbyShops(?array $loc, int $limit = 8): Collection
    {
        if (!$loc) return collect();

        $selling = Inventory::query()->sellable()->distinct()->pluck('business_account')->map(fn ($a) => (string) $a)->all();

        return $this->catalog->context()['inRange']
            ->filter(fn ($s) => in_array((string) $s->account, $selling, true))
            ->take($limit)
            ->map(function ($s) {
                $km = round($s->distance_m / 1000, 1);

                return (object) [
                    'id' => $s->id,
                    'name' => $s->name,
                    'distance_m' => $s->distance_m,
                    'distance_km' => $km,
                    'eta_min' => (int) (20 + ceil($km * 6)),
                    'initial' => mb_strtoupper(mb_substr(trim((string) $s->name) ?: 'W', 0, 1)),
                    'url' => route('water.shop', $s->id),
                ];
            })
            ->values();
    }

    /* ------------------------------------------------------------------ */

    /** A list page: one option (refill / bottled / truck), or one shop's whole price list. */
    private function list(Request $request, ?string $option, ?Business $shop = null, ?string $shopIntro = null)
    {
        $loc = $this->location->get($request);
        $ctx = $this->catalog->context();
        $meta = $option ? $this->options()[$option] : null;

        $offers = $this->offers($loc, $shop?->account);
        if ($option) $offers = $offers->where('option', $option);

        $sizes = $offers->pluck('size')->filter()->unique()
            ->sortBy(fn ($s) => (float) str_replace(',', '', $s) * (str_contains($s, 'ml') ? 0.001 : 1))->values();
        $size = (string) $request->query('size', '');
        if ($size !== '' && $sizes->contains($size)) {
            $offers = $offers->where('size', $size);
        } else {
            $size = '';
        }

        $sortable = $loc && !$shop ? self::SORTS : array_diff_key(self::SORTS, ['near' => 1]);
        $sort = array_key_exists($request->query('sort'), $sortable) ? $request->query('sort') : ($shop ? 'size' : ($loc ? 'near' : 'price'));

        return view('Water.pages.list', [
            'option' => $option,
            'title' => $shop ? $shop->name : $meta['label'],
            'intro' => $shop ? $shopIntro : $meta['intro'],
            'icon' => $shop ? 'fa-solid fa-store' : $meta['icon'],
            'offers' => $this->sorted($offers, $sort)->values(),
            'sizes' => $sizes,
            'size' => $size,
            'sort' => $sort,
            'sorts' => $sortable,
            'hasLocation' => (bool) $loc,
            'outOfRange' => $ctx['outOfRange'],
            'shop' => $shop,
            'options' => $this->options(),
        ]);
    }

    /** Every offer this buyer may see (optionally one shop's). */
    private function offers(?array $loc, ?string $account = null): Collection
    {
        try {
            $rows = Inventory::query()
                ->sellable()
                ->when($account, fn ($q) => $q->where('business_account', $account))
                ->with(['container', 'business:id,account,name,latitude,longitude,shop_status'])
                ->get();
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }

        Inventory::preloadPictures($rows->pluck('water_container_id'));

        return $rows->filter(fn ($r) => $r->business && $r->container)
            ->flatMap(fn ($r) => $this->offersFor($r, $loc))
            ->values();
    }

    /** The options one inventory row sells: trucks sell clean water; bottles a refill and/or a new bottle. */
    private function offersFor(Inventory $row, ?array $loc): Collection
    {
        $pic = $row->picture();
        $distance = $loc && $row->business ? $this->catalog->metres($loc, $row->business) : null;
        $options = $this->options();

        return collect($row->isTruck() ? ['truck'] : ['refill', 'bottled'])
            ->map(function ($option) use ($row, $pic, $distance, $options) {
                $price = $row->priceFor($option);
                if (!$price) return null;

                return (object) [
                    'id' => $row->id,
                    'option' => $option,
                    'type' => $options[$option]['type'],
                    'name' => $row->nameFor($option),
                    'brand' => $pic['brand'],
                    'size' => $row->sizeLabel(),
                    'container' => $row->container?->name,
                    'image' => $pic['image'],
                    'price' => $price,
                    'shop' => $row->business->name ?? 'Water shop',
                    'shop_id' => $row->business->id ?? null,
                    'shop_account' => $row->business_account,
                    'distance_m' => $distance,
                    'url' => route('water.product', $row->id),
                ];
            })
            ->filter()
            ->values();
    }

    private function sorted(Collection $offers, string $sort): Collection
    {
        $litres = fn ($o) => (float) str_replace(',', '', $o->size) * (str_contains($o->size, 'ml') ? 0.001 : 1);

        return match ($sort) {
            'near' => $offers->sortBy([fn ($a, $b) => ($a->distance_m ?? PHP_INT_MAX) <=> ($b->distance_m ?? PHP_INT_MAX), fn ($a, $b) => $a->price <=> $b->price]),
            'price-hi' => $offers->sortByDesc('price'),
            'size' => $offers->sortBy([fn ($a, $b) => $litres($a) <=> $litres($b), fn ($a, $b) => strcmp($a->option, $b->option)]),
            default => $offers->sortBy('price'),
        };
    }

    private function options(): array
    {
        return config('water.options');
    }
}
