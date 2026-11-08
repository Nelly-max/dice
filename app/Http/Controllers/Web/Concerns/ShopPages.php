<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Support\ShopCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Category, Hot sales and Online exclusives pages, and the category tiles / rows on the home
 * page, shared by the four product shops. The controller using it has $this->shops
 * (ShopDiscovery) and $this->presenter (ProductPresenter) and says which shop it is.
 *
 *   Hot sales          discounts running now, best sellers first (customer.order_items, last 60 days)
 *   Online exclusives  every discount running now in shops with an active subscription that
 *                      includes e-commerce (catalogue() already requires that), biggest first
 *
 * Both follow the home page's rules (cart shop / delivery radius) so every item opens and can
 * be added to the cart; with no location set they cover every such shop.
 */
trait ShopPages
{
    /** 'homemarket' | 'liquorhub' | 'glamroom' | 'farmmarket' */
    abstract protected function shopKey(): string;

    /**
     * A restricted catalogue() query for GROUP BY counts: its select list is replaced, so the
     * bindings of restrict()'s "distance AS distance_meters" column have to go too.
     */
    private function groupable(array $context)
    {
        $query = $this->shops->restrict($this->shops->catalogue(), $context)->reorder();
        $query->bindings['select'] = [];

        return $query;
    }

    /** /<shop>/category/{id}?sub=&brand=&sort=&offers=1 */
    public function category(Request $request, int $id)
    {
        $meta = ShopCatalog::meta($this->shopKey());
        $category = ShopCatalog::category($this->shopKey(), $id);
        abort_unless($category, 404);

        $context = $this->shops->context($request);
        $query = $this->shops->restrict($this->shops->catalogue(), $context)->where('p.category_id', $id);

        $subs = ShopCatalog::subCategories($this->shopKey(), $id);
        $sub = (int) $request->query('sub');
        if ($sub && $subs->contains('id', $sub)) {
            $query->where('p.subcategory_id', $sub);
        }
        if ($request->boolean('offers')) {
            ShopCatalog::onOffer($query);
        }

        // brands in this category for this buyer (sidebar filter)
        $brands = collect();
        try {
            $brands = $this->groupable($context)->where('p.category_id', $id)
                ->whereNotNull('p.brand')->where('p.brand', '!=', '')
                ->select('p.brand')->selectRaw('COUNT(*) as n')->groupBy('p.brand')
                ->orderByDesc('n')->limit(20)->pluck('n', 'p.brand');
        } catch (\Throwable $e) {
            report($e);
        }
        $brand = (string) $request->query('brand', '');
        if ($brand !== '' && $brands->has($brand)) {
            $query->where('p.brand', $brand);
        } else {
            $brand = '';
        }

        // sub-categories with something for this buyer only
        try {
            $subCounts = $this->groupable($context)->where('p.category_id', $id)
                ->select('p.subcategory_id')->selectRaw('COUNT(*) as n')->groupBy('p.subcategory_id')
                ->pluck('n', 'p.subcategory_id');
            $subs = $subs->map(fn ($s) => (object) ['id' => $s->id, 'name' => $s->name, 'count' => (int) ($subCounts[$s->id] ?? 0)])
                ->filter(fn ($s) => $s->count > 0 || $s->id === $sub)->values();
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->listing($request, $query, $context, [
            'kind'     => 'category',
            'title'    => $category->name,
            'icon'     => ShopCatalog::icon($category->name),
            'category' => $category,
            'subs'     => $subs,
            'sub'      => $sub,
            'brands'   => $brands,
            'brand'    => $brand,
        ]);
    }

    /** /<shop>/deals — Hot sales near the buyer. */
    public function deals(Request $request)
    {
        $context = $this->shops->context($request);
        $query = ShopCatalog::onOffer($this->shops->restrict($this->shops->catalogue(), $context));

        return $this->listing($request, $query, $context, [
            'kind'  => 'deals',
            'title' => 'Hot sales',
            'icon'  => 'fa-solid fa-fire',
            'intro' => 'Best-selling offers running now in shops that deliver to you.',
        ], 'popular');
    }

    /** /<shop>/online-exclusives — every discount from shops selling online (e-commerce subscription). */
    public function exclusives(Request $request)
    {
        $context = $this->shops->context($request);
        $query = ShopCatalog::onOffer($this->shops->restrict($this->shops->catalogue(), $context));

        return $this->listing($request, $query, $context, [
            'kind'  => 'exclusives',
            'title' => 'Online exclusives',
            'icon'  => 'fa-solid fa-bolt',
            'intro' => 'Discounts you only get when you order online on Smart Market.',
        ], 'discount');
    }

    /** One listing page: sort, page of 40, plus the earliest end date of the offers on it. */
    private function listing(Request $request, $query, array $context, array $page, string $defaultSort = 'new')
    {
        $meta = ShopCatalog::meta($this->shopKey());
        $sort = array_key_exists($request->query('sort'), ShopCatalog::sorts()) ? $request->query('sort') : $defaultSort;

        $products = ShopCatalog::applySort($query, $sort, $this->shopKey())
            ->simplePaginate(40)
            ->withQueryString()
            ->through(fn ($row) => $this->presenter->present($row));

        $endsAt = collect($products->items())
            ->filter(fn ($p) => $p->has_discount && !empty($p->discount_stop))
            ->map(fn ($p) => \Carbon\Carbon::parse($p->discount_stop))
            ->filter(fn ($d) => $d->isFuture() && $d->lte(now()->addDays(7)))
            ->min();

        return view('shop.listing', array_merge($page, [
            'meta'         => $meta,
            'products'     => $products,
            'sort'         => $sort,
            'sorts'        => ShopCatalog::sorts(),
            'endsAt'       => $endsAt,
            'categories'   => ShopCatalog::categories($this->shopKey()),
            'subs'         => $page['subs'] ?? collect(),
            'sub'          => $page['sub'] ?? 0,
            'brands'       => $page['brands'] ?? collect(),
            'brand'        => $page['brand'] ?? '',
            'hasLocation'  => !is_null($context['location']),
            'cartShopName' => $this->shops->shopName($context['shop']),
        ]));
    }

    /**
     * For the home page: category tiles (sales tiles first, then categories that have items for
     * this buyer, each with a product photo), and a row of products for the biggest categories.
     */
    protected function shopHome(array $context, Collection $products): array
    {
        $shop = $this->shopKey();
        $meta = ShopCatalog::meta($shop);
        $categories = ShopCatalog::categories($shop);

        // items per category for this buyer + one item id per category for its picture
        $counts = collect();
        try {
            $now = now();
            $counts = $this->groupable($context)
                ->select('p.category_id')
                ->selectRaw('COUNT(*) as n, MAX(ri.id) as sample_id')
                ->selectRaw('SUM(CASE WHEN ri.discount > 0 AND (ri.discount_start IS NULL OR ri.discount_start <= ?) AND (ri.discount_stop IS NULL OR ri.discount_stop >= ?) THEN 1 ELSE 0 END) as offers', [$now, $now])
                ->groupBy('p.category_id')
                ->get()
                ->keyBy('category_id');
        } catch (\Throwable $e) {
            report($e);
        }

        $pictures = [];
        $sampleIds = $counts->pluck('sample_id')->filter()->all();
        if ($sampleIds) {
            try {
                $pictures = $this->shops->catalogue()->whereIn('ri.id', $sampleIds)->get()
                    ->mapWithKeys(fn ($row) => [$row->category_id => $this->presenter->present($row)->image_url])
                    ->all();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // a picture from config/<shop>.php category_tiles wins when its label matches
        $icons = collect(config($shop . '.category_tiles', []))
            ->filter(fn ($t) => !empty($t['file']))
            ->mapWithKeys(fn ($t) => [mb_strtolower($t['label']) => $t['file']]);
        $mediaClass = '\\App\\Services\\' . $meta['views'] . '\\MediaGallery';

        $tiles = $categories
            ->filter(fn ($c) => ($counts[$c->id]->n ?? 0) > 0)
            ->sortByDesc(fn ($c) => $counts[$c->id]->n ?? 0)
            ->map(fn ($c) => (object) [
                'id'    => $c->id,
                'name'  => $c->name,
                'count' => (int) ($counts[$c->id]->n ?? 0),
                'offers'=> (int) ($counts[$c->id]->offers ?? 0),
                'url'   => route($meta['route'] . '.category', $c->id),
                'image' => isset($icons[mb_strtolower($c->name)])
                    ? app($mediaClass)->categoryIcon($icons[mb_strtolower($c->name)])
                    : ($pictures[$c->id] ?? null),
                'icon'  => ShopCatalog::icon($c->name),
            ])
            ->values();

        // offers: near the buyer (from the home page's items) and in any online shop
        $dealsCount = $products->where('has_discount', true)->count();
        $dealsTop = (int) $products->where('has_discount', true)->max('discount_percentage');
        // every running discount this buyer can order (same rules as the page it opens)
        try {
            $exclusives = ShopCatalog::onOffer($this->shops->restrict($this->shops->catalogue(), $context))->count();
        } catch (\Throwable $e) {
            report($e);
            $exclusives = 0;
        }

        // the shop's own pictures for the two offer tiles (config/<shop>.php category_tiles)
        $offerPics = [];
        foreach (config($shop . '.category_tiles', []) as $t) {
            $label = mb_strtolower($t['label'] ?? '');
            if (!empty($t['file']) && (str_contains($label, 'hot') || str_contains($label, 'exclusive'))) {
                $offerPics[str_contains($label, 'hot') ? 'hot' : 'excl'] = $t['file'];
            }
        }

        $endsAt = $products->where('has_discount', true)
            ->pluck('discount_stop')->filter()
            ->map(fn ($d) => \Carbon\Carbon::parse($d))
            ->filter(fn ($d) => $d->isFuture() && $d->lte(now()->addDays(7)))
            ->min();

        // a row for each of the biggest categories in the home page's items
        $names = $categories->keyBy('id');
        $categoryRows = $products->groupBy('category_id')
            ->filter(fn ($items, $id) => $items->count() >= 4 && isset($names[$id]))
            ->sortByDesc(fn ($items) => $items->count())
            ->take(4)
            ->map(fn ($items, $id) => (object) [
                'title'    => $names[$id]->name,
                'url'      => route($meta['route'] . '.category', $id),
                'products' => $items->unique('product_id')->take(config($shop . '.row_size', 20))->values(),
            ])
            ->values();

        return [
            'meta'          => $meta,
            'tiles'         => $tiles,
            'deals'         => max($dealsCount, 0),
            'dealsTop'      => $dealsTop,
            'dealsUrl'      => route($meta['route'] . '.deals'),
            'exclusives'    => (int) $exclusives,
            'exclusivesUrl' => route($meta['route'] . '.exclusives'),
            'hotPic'        => isset($offerPics['hot']) ? app($mediaClass)->categoryIcon($offerPics['hot']) : null,
            'exclPic'       => isset($offerPics['excl']) ? app($mediaClass)->categoryIcon($offerPics['excl']) : null,
            'endsAt'        => $endsAt,
            'categoryRows'  => $categoryRows,
        ];
    }
}
