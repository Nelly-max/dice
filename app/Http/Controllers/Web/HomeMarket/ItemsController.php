<?php

namespace App\Http\Controllers\Web\HomeMarket;

use App\Http\Controllers\Controller;
use App\Services\HomeMarket\MediaGallery;
use App\Services\HomeMarket\ProductPresenter;
use App\Services\HomeMarket\ShopDiscovery;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemsController extends Controller
{
    public function __construct(
        private ShopDiscovery $shops,
        private ProductPresenter $presenter,
        private MediaGallery $media,
    ) {
    }

    /**
     * HomeMarket home page.
     *
     * Shop rules (see ShopDiscovery): once the cart holds items, only that
     * shop's inventory is shown; otherwise only shops within the maximum
     * delivery radius of the buyer's saved location.
     */
    public function index(Request $request)
    {
        $context = $this->shops->context($request);

        $query = $this->shops->restrict($this->shops->catalogue(), $context);

        if ($request->filled('category')) {
            $query->where('p.category_id', (int) $request->query('category'));
        }

        $products = $query
            ->latest('ri.updated_at')
            ->limit(config('homemarket.home_item_limit'))
            ->get()
            ->map(fn ($row) => $this->presenter->present($row));

        $size = config('homemarket.row_size');

        $rows = [
            'hot'      => $products->where('has_discount', true)->sortByDesc('discount_percentage')->take($size)->values(),
            'fresh'    => $products->take($size)->values(),
            'everyday' => $products->slice($size)->take($size * 2)->values(),
        ];

        return view('HomeMarket.home', [
            'rows'         => $rows,
            'hasLocation'  => !is_null($context['location']),
            'outOfRange'   => $context['location'] && !$context['shop'] && is_null($context['radius']),
            'cartShopName' => $this->shops->shopName($context['shop']),
            'nearbyShops'  => ($context['location'] && $context['radius'] && !$context['shop'])
                ? $this->shops->nearbyShops($context['location'], $context['radius'])
                : collect(),
            'radiusKm'     => $context['radius'] ? $context['radius'] / 1000 : null,
            'hero'         => $this->media->images('Sliders'),
            'leaflets'     => [
                1 => $this->media->images('Carousel_1'),
                2 => $this->media->images('Carousel_2'),
                3 => $this->media->images('Carousel_3'),
            ],
            'banners'      => [
                1 => $this->media->images('StaticBanners_1'),
                2 => $this->media->images('StaticBanners_2'),
                3 => $this->media->images('StaticBanners_3'),
            ],
        ]);
    }

    /**
     * A single inventory item, with the other sizes the same shop stocks.
     * The same shop / location rules as the home page apply.
     */
    public function ViewItem($inventoryId, Request $request)
    {
        $context = $this->shops->context($request);

        $product = $this->shops
            ->restrict($this->shops->catalogue(), $context)
            ->where('ri.id', $inventoryId)
            ->first();

        abort_if(
            !$product,
            404,
            'This item could not be found or is not available from your shop or delivery area.'
        );

        $product = $this->presenter->present($product);

        $product->gallery_images = $this->shops->catalogue()
            ->where('ri.business_account', $product->business_account)
            ->where('ri.product_id', $product->product_id)
            ->where('pi.packaging_id', $product->packaging_id)
            ->get()
            ->unique('item_id')
            ->map(fn ($row) => $this->presenter->present($row))
            ->values();

        [$similar, $sameBrand] = $this->related($product, $context);

        return view('HomeMarket.products.viewItem', compact('product', 'similar', 'sameBrand'));
    }

    /**
     * Rows under the product: other products with the same generic name
     * (products.generic_id), then other products of the same brand. Same shop /
     * delivery-area rules as everything else, one card per product, the
     * product being viewed left out.
     *
     * @return array{0: Collection, 1: Collection}
     */
    private function related(object $product, array $context): array
    {
        $similar = collect();
        $sameBrand = collect();

        $pick = function ($query, array $exclude) {
            return $query
                ->whereNotIn('p.id', $exclude)
                ->latest('ri.updated_at')
                ->limit(60)
                ->get()
                ->unique('product_id')
                ->take(12)
                ->map(fn ($row) => $this->presenter->present($row))
                ->values();
        };

        try {
            $genericId = DB::connection('homemarket')->table('products')
                ->where('id', $product->product_id)
                ->value('generic_id');

            if ($genericId) {
                $similar = $pick(
                    $this->shops->restrict($this->shops->catalogue(), $context)->where('p.generic_id', $genericId),
                    [$product->product_id]
                );
            }

            if (!empty($product->brand)) {
                $sameBrand = $pick(
                    $this->shops->restrict($this->shops->catalogue(), $context)->where('p.brand', $product->brand),
                    array_merge([$product->product_id], $similar->pluck('product_id')->all())
                );
            }
        } catch (\Throwable $e) {
            // related rows are a nice-to-have; never break the product page over them
            Log::warning('Related items failed for product ' . $product->product_id . ': ' . $e->getMessage());
        }

        return [$similar, $sameBrand];
    }

    /**
     * Search within the shops the buyer can actually order from (same shop /
     * location rules as the home page).
     *
     * - ?q=milk with Accept: JSON (or ?json=1) -> compact suggestions for the live search panel
     * - ?q=milk in the browser                 -> a full results page
     */
    public function search(Request $request)
    {
        $term = trim(mb_substr((string) $request->query('q', ''), 0, 60));
        $context = $this->shops->context($request);
        $wantsJson = $request->expectsJson() || $request->boolean('json');

        $query = $this->matching($term, $context);

        if ($wantsJson) {
            if (mb_strlen($term) < 2) {
                return response()->json(['q' => $term, 'products' => [], 'shops' => [], 'categories' => [], 'more' => false]);
            }

            $rows = $query->limit(9)->get()->map(fn ($row) => $this->presenter->present($row));

            $shops = collect();
            if ($context['location'] && $context['radius'] && !$context['shop']) {
                $shops = $this->shops->nearbyShops($context['location'], $context['radius'], 50)
                    ->filter(fn ($shop) => mb_stripos($shop->name, $term) !== false)
                    ->take(3)->values();
            }

            $categories = collect(config('homemarket.nav_categories'))
                ->filter(fn ($c) => $c['category_id'] && mb_stripos($c['label'], $term) !== false)
                ->take(3)
                ->map(fn ($c) => ['label' => $c['label'], 'url' => route('homemarket.home', ['category' => $c['category_id']])])
                ->values();

            return response()->json([
                'q'          => $term,
                'more'       => $rows->count() > 8,
                'products'   => $rows->take(8)->map(fn ($p) => [
                    'name'     => $p->product_name,
                    'variant'  => trim($p->variant_label . (!empty($p->packaging_name) ? ' (' . strtolower($p->packaging_name) . ')' : '')),
                    'price'    => (int) round($p->final_price),
                    'was'      => $p->has_discount ? (int) round($p->retail_price) : null,
                    'discount' => $p->has_discount ? $p->discount_percentage : null,
                    'image'    => $p->image_url,
                    'shop'     => $p->business_name,
                    'km'       => $p->distance_km,
                    'url'      => route('item.view', $p->id),
                ])->values(),
                'shops'      => $shops->map(fn ($s) => [
                    'name' => $s->name, 'km' => $s->distance_km, 'eta' => $s->eta_min,
                    'url'  => route('homemarket.search', ['q' => $s->name]),
                ])->values(),
                'categories' => $categories,
            ]);
        }

        $products = mb_strlen($term) >= 2
            ? $query->limit(60)->get()->map(fn ($row) => $this->presenter->present($row))
            : collect();

        return view('HomeMarket.search', [
            'term'         => $term,
            'products'     => $products,
            'hasLocation'  => !is_null($context['location']),
            'outOfRange'   => $context['location'] && !$context['shop'] && is_null($context['radius']),
            'cartShopName' => $this->shops->shopName($context['shop']),
        ]);
    }

    /** Catalogue rows whose product name, brand or shop name contains the term; best matches first. */
    private function matching(string $term, array $context)
    {
        $escaped = addcslashes($term, '%_\\');

        return $this->shops->restrict($this->shops->catalogue(), $context)
            ->where(function ($q) use ($escaped) {
                $q->where('p.name', 'like', "%{$escaped}%")
                  ->orWhere('p.brand', 'like', "%{$escaped}%")
                  ->orWhere('b.name', 'like', "%{$escaped}%");
            })
            ->orderByRaw('(p.name LIKE ?) DESC', ["{$escaped}%"])
            ->orderByRaw('(p.name LIKE ?) DESC', ["%{$escaped}%"])
            ->latest('ri.updated_at');
    }
}
