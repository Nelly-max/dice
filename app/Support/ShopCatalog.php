<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What the four product shops (Home Market, Liquor Hub, Glam Room, Farm Market) share for
 * categories and offers: which database / views / routes each uses, their category lists,
 * and the "discount is running now" rule (same as each ProductPresenter::pricing()).
 */
class ShopCatalog
{
    /** shop key => settings. 'route' is the route-name prefix, 'item' the product page route,
     *  'stock' the order_items.stockable_type of its inventory (config/stockables.php). */
    public const SHOPS = [
        'homemarket' => [
            'name' => 'Home Market', 'connection' => 'homemarket', 'views' => 'HomeMarket',
            'route' => 'homemarket', 'stock' => 'retail_inventory', 'item' => 'item.view', 'media' => 'homeMarket',
        ],
        'liquorhub' => [
            'name' => 'Liquor Hub', 'connection' => 'liquorhub', 'views' => 'LiquorHub',
            'route' => 'liquorhub', 'stock' => 'liquor_inventory', 'item' => 'liquorhub.item.view', 'media' => 'liquorHub',
        ],
        'glamroom' => [
            'name' => 'Glam Room', 'connection' => 'glamroom', 'views' => 'GlamRoom',
            'route' => 'glamroom', 'stock' => 'glam_inventory', 'item' => 'glamroom.item.view', 'media' => 'glamRoom',
        ],
        'farmmarket' => [
            'name' => 'Farm Market', 'connection' => 'farmmarket', 'views' => 'FarmMarket',
            'route' => 'farmmarket', 'stock' => 'farm_inventory', 'item' => 'farmmarket.item.view', 'media' => 'farmMarket',
        ],
    ];

    public static function meta(string $shop): array
    {
        return self::SHOPS[$shop] + ['key' => $shop];
    }

    /** The shop's categories (id, name), A–Z. Cached 10 minutes; empty if the table can't be read. */
    public static function categories(string $shop): Collection
    {
        return Cache::remember("shopcatalog.{$shop}.categories", 60,   // a minute: new categories show quickly
            function () use ($shop) {
            try {
                return DB::connection(self::SHOPS[$shop]['connection'])
                    ->table('categories')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($c) => (object) ['id' => (int) $c->id, 'name' => trim($c->name)])
                    ->values();
            } catch (\Throwable $e) {
                report($e);
                return collect();
            }
        });
    }

    public static function category(string $shop, int $id): ?object
    {
        return self::categories($shop)->firstWhere('id', $id);
    }

    /** Sub-categories of one category (id, name). */
    public static function subCategories(string $shop, int $categoryId): Collection
    {
        return Cache::remember("shopcatalog.{$shop}.sub.{$categoryId}", 60, function () use ($shop, $categoryId) {
            try {
                return DB::connection(self::SHOPS[$shop]['connection'])
                    ->table('sub_categories')
                    ->where('category_id', $categoryId)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($c) => (object) ['id' => (int) $c->id, 'name' => trim($c->name)])
                    ->values();
            } catch (\Throwable $e) {
                return collect();
            }
        });
    }

    /** Limit a catalogue() query to items whose discount is running now (amount > 0, inside its start / stop window). */
    public static function onOffer(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('ri.discount', '>', 0)
            ->where(fn ($q) => $q->whereNull('ri.discount_start')->orWhere('ri.discount_start', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ri.discount_stop')->orWhere('ri.discount_stop', '>=', $now));
    }

    /**
     * Units sold per inventory row (ri.id) in the last 60 days, best sellers first.
     * From customer.order_items; cached 10 minutes; empty when it can't be read.
     *
     * @return array<int, int>  ri.id => units sold
     */
    public static function bestSellers(string $shop): array
    {
        return Cache::remember("shopcatalog.{$shop}.bestsellers", 600, function () use ($shop) {
            try {
                return DB::connection('customer')
                    ->table('order_items')
                    ->where('stockable_type', self::SHOPS[$shop]['stock'])
                    ->where('created_at', '>=', now()->subDays(60))
                    ->groupBy('stockable_id')
                    ->selectRaw('stockable_id, SUM(quantity) as sold')
                    ->orderByDesc('sold')
                    ->limit(300)
                    ->pluck('sold', 'stockable_id')
                    ->map(fn ($n) => (int) $n)
                    ->all();
            } catch (\Throwable $e) {
                report($e);
                return [];
            }
        });
    }

    /** Sort options for listing pages: key => label. */
    public static function sorts(): array
    {
        return [
            'popular'  => 'Best selling',
            'new'      => 'Newest',
            'price'    => 'Price: low to high',
            'price-hi' => 'Price: high to low',
            'discount' => 'Biggest discount',
            'name'     => 'Name A–Z',
        ];
    }

    public static function applySort(Builder $query, string $sort, ?string $shop = null): Builder
    {
        if ($sort === 'popular') {
            $ids = $shop ? array_keys(self::bestSellers($shop)) : [];
            if ($ids) {
                // FIELD() gives the best seller the highest position; unsold rows get 0
                $ids = array_reverse(array_map('intval', $ids));
                $query->orderByRaw('FIELD(ri.id, ' . implode(',', $ids) . ') DESC');
            }
            return $query->orderByRaw('(COALESCE(ri.discount, 0) / NULLIF(ri.retail_price, 0)) DESC')->latest('ri.updated_at');
        }

        return match ($sort) {
            'price'    => $query->orderByRaw('(ri.retail_price - COALESCE(ri.discount, 0)) ASC'),
            'price-hi' => $query->orderByRaw('(ri.retail_price - COALESCE(ri.discount, 0)) DESC'),
            'discount' => $query->orderByRaw('(COALESCE(ri.discount, 0) / NULLIF(ri.retail_price, 0)) DESC'),
            'name'     => $query->orderBy('p.name'),
            default    => $query->latest('ri.updated_at'),
        };
    }

    /** Font Awesome icon for a category name (used when there is no picture). */
    public static function icon(string $name): string
    {
        $n = mb_strtolower($name);

        return match (true) {
            (bool) preg_match('/drink|beverage|juice|soda|water/', $n) => 'fa-solid fa-bottle-water',
            (bool) preg_match('/whisk|vodka|gin|rum|brandy|spirit|liquor/', $n) => 'fa-solid fa-whiskey-glass',
            (bool) preg_match('/wine|champagne/', $n) => 'fa-solid fa-wine-glass',
            (bool) preg_match('/beer|cider/', $n) => 'fa-solid fa-beer-mug-empty',
            (bool) preg_match('/dairy|dairies|milk|cheese|yogh?urt/', $n) => 'fa-solid fa-cheese',
            (bool) preg_match('/egg/', $n) => 'fa-solid fa-egg',
            (bool) preg_match('/meat|beef|chicken|poultry/', $n) => 'fa-solid fa-drumstick-bite',
            (bool) preg_match('/fish|sea ?food/', $n) => 'fa-solid fa-fish',
            (bool) preg_match('/fruit/', $n) => 'fa-solid fa-apple-whole',
            (bool) preg_match('/vegetable|greens/', $n) => 'fa-solid fa-carrot',
            (bool) preg_match('/cereal|grain|flour|maize|rice/', $n) => 'fa-solid fa-wheat-awn',
            (bool) preg_match('/legume|bean|pulse|seed/', $n) => 'fa-solid fa-seedling',
            (bool) preg_match('/bak|bread|cake/', $n) => 'fa-solid fa-bread-slice',
            (bool) preg_match('/sweet|confection|chocolate|snack|biscuit/', $n) => 'fa-solid fa-candy-cane',
            (bool) preg_match('/clean|detergent|soap|laundry/', $n) => 'fa-solid fa-soap',
            (bool) preg_match('/hygiene|personal|toilet|tissue/', $n) => 'fa-solid fa-pump-soap',
            (bool) preg_match('/baby|kid/', $n) => 'fa-solid fa-baby',
            (bool) preg_match('/house ?hold|home|kitchen/', $n) => 'fa-solid fa-house',
            (bool) preg_match('/skin|face|serum/', $n) => 'fa-solid fa-pump-soap',
            (bool) preg_match('/make ?up|cosmetic|lip/', $n) => 'fa-solid fa-wand-magic-sparkles',
            (bool) preg_match('/hair|wig|braid/', $n) => 'fa-solid fa-scissors',
            (bool) preg_match('/fragrance|perfume|scent/', $n) => 'fa-solid fa-spray-can-sparkles',
            (bool) preg_match('/nail/', $n) => 'fa-solid fa-hand-sparkles',
            (bool) preg_match('/food|grocer|pantry|cooking|oil/', $n) => 'fa-solid fa-basket-shopping',
            default => 'fa-solid fa-tag',
        };
    }
}
