<?php

namespace App\Services\GlamRoom;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Which Glam Room shops and items a buyer may see (cart shop, delivery location,
 * radius). All of it lives in App\Services\Shop\ShopDiscovery, shared by the four
 * product shops; this class only names Glam Room's database, stock type and columns.
 */
class ShopDiscovery extends \App\Services\Shop\ShopDiscovery
{
    protected const CONNECTION = 'glamroom';

    protected const STOCKABLE = 'glam_inventory';

    protected const EXTRA_COLUMNS = ['pi.shade', 'pi.shade_hex'];

    /*
    |--------------------------------------------------------------------------
    | Glam Room: Shop by (skin type, concern, hair type ...) and brand
    |--------------------------------------------------------------------------
    */

    /**
     * Active groups with their options, for the "Shop by" chips.
     * Each: {name, slug, options: [{name, slug, key}]} where key = "group.option".
     */
    public function shopByMenu(): \Illuminate\Support\Collection
    {
        try {
            $rows = DB::connection('glamroom')
                ->table('shop_by_options as o')
                ->join('shop_by_groups as g', 'g.id', '=', 'o.group_id')
                ->where('g.is_active', 1)
                ->where('o.is_active', 1)
                ->orderBy('g.sort_order')->orderBy('g.name')
                ->orderBy('o.sort_order')->orderBy('o.name')
                ->get(['g.name as group_name', 'g.slug as group_slug', 'o.name', 'o.slug']);
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }

        return $rows->groupBy('group_slug')->map(fn ($options) => (object) [
            'name'    => $options->first()->group_name,
            'slug'    => $options->first()->group_slug,
            'options' => $options->map(fn ($o) => (object) [
                'name' => $o->name,
                'slug' => $o->slug,
                'key'  => $o->group_slug . '.' . $o->slug,
            ])->values(),
        ])->values();
    }

    /**
     * Limit a catalogue() query to products tagged with "group.option",
     * e.g. "skin-type.oily". Unknown keys return the query unchanged.
     */
    public function whereShopBy(Builder $query, ?string $key): Builder
    {
        if (!$key || !str_contains($key, '.')) {
            return $query;
        }

        [$group, $option] = explode('.', $key, 2);

        return $query->whereExists(function ($sub) use ($group, $option) {
            $sub->select(DB::raw(1))
                ->from('product_shop_by as ps')
                ->join('shop_by_options as so', 'so.id', '=', 'ps.option_id')
                ->join('shop_by_groups as sg', 'sg.id', '=', 'so.group_id')
                ->whereColumn('ps.product_id', 'p.id')
                ->where('sg.slug', $group)
                ->where('so.slug', $option);
        });
    }

    /** Readable label for a shop-by key: "Skin Type: Oily". */
    public function shopByLabel(?string $key): ?string
    {
        foreach ($this->shopByMenu() as $group) {
            foreach ($group->options as $option) {
                if ($option->key === $key) {
                    return "{$group->name}: {$option->name}";
                }
            }
        }

        return null;
    }

    /** Brands with stock the buyer can order, most items first. */
    public function topBrands(array $context, int $limit = 12): \Illuminate\Support\Collection
    {
        try {
            return $this->restrict($this->catalogue(), $context)
                ->whereNotNull('p.brand')
                ->where('p.brand', '<>', '')
                ->limit(500)
                ->get()
                ->pluck('brand')
                ->countBy()
                ->sortDesc()
                ->keys()
                ->take($limit)
                ->values();
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }
    }

    /** ['Skin Type' => ['Oily', 'Combination'], ...] for one product. */
    public function productShopBy(int $productId): array
    {
        try {
            return DB::connection('glamroom')
                ->table('product_shop_by as ps')
                ->join('shop_by_options as so', 'so.id', '=', 'ps.option_id')
                ->join('shop_by_groups as sg', 'sg.id', '=', 'so.group_id')
                ->where('ps.product_id', $productId)
                ->orderBy('sg.sort_order')->orderBy('so.sort_order')
                ->get(['sg.name as group_name', 'so.name'])
                ->groupBy('group_name')
                ->map(fn ($rows) => $rows->pluck('name')->all())
                ->all();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }
}
