<?php

namespace App\Services\HomeMarket;

use App\Models\Customer\Cart;
use App\Services\DeliveryLocationService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Everything HomeMarket needs to decide WHICH shops and items a buyer may see:
 * the shop their cart is locked to, their delivery location and the maximum
 * delivery radius. Used by the home page, the item page and the cart.
 */
class ShopDiscovery
{
    public function __construct(private DeliveryLocationService $location)
    {
    }

    /*
    |--------------------------------------------------------------------------
    | Buyer context
    |--------------------------------------------------------------------------
    */

    /**
     * The shop the buyer's active cart is locked to (business account), if any.
     */
    public function cartShop(): ?string
    {
        return Cart::forCurrent()
            ->whereNotNull('business_account')
            ->value('business_account');
    }

    /**
     * Priority: 1. the cart's shop, 2. the delivery location, 3. every shop.
     *
     * @return array{shop: ?string, location: ?array, radius: ?int}
     */
    public function context(Request $request): array
    {
        $shop = $this->cartShop();
        $location = $this->location->get($request);

        $radius = (!$shop && $location)
            ? $this->nearestRadius($location['latitude'], $location['longitude'])
            : null;

        return compact('shop', 'location', 'radius');
    }

    public function shopName(?string $businessAccount): ?string
    {
        if (!$businessAccount) {
            return null;
        }

        return DB::connection('homemarket')
            ->table('business')
            ->where('account', $businessAccount)
            ->value('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Queries
    |--------------------------------------------------------------------------
    */

    /**
     * Every sellable inventory row with its product, item, units, packaging,
     * shop and primary image. Callers add their own filters.
     */
    public function catalogue(): Builder
    {
        return DB::connection('homemarket')
            ->table('retail_inventory as ri')
            ->join('business as b', 'b.account', '=', 'ri.business_account')
            ->join('business_subscription as bs', function ($join) {
                $join->on('bs.main_branch_account', '=', 'b.main_branch_account')
                    ->where('bs.status', 'active');
            })
            ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
            ->join('products as p', 'p.id', '=', 'ri.product_id')
            ->join('product_items as pi', 'pi.id', '=', 'ri.item_id')
            ->leftJoin('product_variables.product_packaging as pv_pack', 'pv_pack.id', '=', 'pi.packaging_id')
            ->leftJoin('product_variables.quantity_units as qu', 'qu.id', '=', 'pi.quantity_unit_id')
            ->leftJoin('product_variables.weight_units as wu', 'wu.id', '=', 'pi.weight_unit_id')
            ->where('t.ecommerce_access', 1)
            ->where('b.shop_status', 'open')
            ->where('ri.status', 'instock')
            ->whereNotNull('ri.retail_price')
            ->select([
                'ri.*',
                'b.name as business_name',
                'b.account as business_account',
                'b.latitude as business_latitude',
                'b.longitude as business_longitude',
                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                'p.brand',
                'p.description',
                'p.category_id',
                'pi.id as item_id',
                'pi.code as item_code',
                'pi.size_value',
                'pi.weight_value',
                'pi.pieces',
                'pi.packaging_id',
                'qu.slug as quantity_unit',
                'wu.name as weight_unit',
                'pv_pack.name as packaging_name',
            ])
            ->selectRaw('(
                SELECT pii.image_path
                FROM product_item_images AS pii
                WHERE pii.product_item_id = pi.id
                ORDER BY pii.is_primary DESC, pii.id ASC
                LIMIT 1
            ) AS image_path');
    }

    /**
     * Apply the buyer's shop / location rules to a catalogue() query.
     */
    public function restrict(Builder $query, array $context): Builder
    {
        if ($context['shop']) {
            return $query->where('ri.business_account', $context['shop']);
        }

        if (!$context['location']) {
            return $query;
        }

        if (is_null($context['radius'])) {
            return $query->whereRaw('1 = 0'); // nothing within the maximum radius
        }

        [$sql, $bindings] = $this->distance(
            $context['location']['latitude'],
            $context['location']['longitude']
        );

        return $query
            ->whereNotNull('b.latitude')
            ->whereNotNull('b.longitude')
            ->whereRaw("{$sql} <= ?", [...$bindings, $context['radius']])
            ->selectRaw("{$sql} AS distance_meters", $bindings);
    }

    /*
    |--------------------------------------------------------------------------
    | Radius
    |--------------------------------------------------------------------------
    */

    /**
     * Smallest configured radius that contains at least one qualifying shop,
     * or null when none is within the maximum. One query, not one per step.
     */
    public function nearestRadius(float $latitude, float $longitude): ?int
    {
        [$sql, $bindings] = $this->distance($latitude, $longitude);

        $nearest = DB::connection('homemarket')
            ->table('business as b')
            ->join('business_subscription as bs', function ($join) {
                $join->on('bs.main_branch_account', '=', 'b.main_branch_account')
                    ->where('bs.status', 'active');
            })
            ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
            ->where('t.ecommerce_access', 1)
            ->where('b.shop_status', 'open')
            ->whereNotNull('b.latitude')
            ->whereNotNull('b.longitude')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('retail_inventory as ri')
                    ->whereColumn('ri.business_account', 'b.account')
                    ->where('ri.status', 'instock')
                    ->whereNotNull('ri.retail_price');
            })
            ->selectRaw("MIN({$sql}) AS nearest", $bindings)
            ->first()
            ->nearest ?? null;

        if (is_null($nearest)) {
            return null;
        }

        foreach (config('homemarket.radius_steps_m') as $step) {
            if ($nearest <= $step) {
                return (int) $step;
            }
        }

        return null;
    }

    /**
     * Open, subscribed shops with stock inside the buyer's radius, nearest first.
     * Feeds the "Shops near you" row. Each row: account, name, distance_m, distance_km, eta_min, initial.
     */
    public function nearbyShops(array $location, int $radius, int $limit = 8): \Illuminate\Support\Collection
    {
        [$sql, $bindings] = $this->distance($location['latitude'], $location['longitude']);

        return DB::connection('homemarket')
            ->table('business as b')
            ->join('business_subscription as bs', function ($join) {
                $join->on('bs.main_branch_account', '=', 'b.main_branch_account')
                    ->where('bs.status', 'active');
            })
            ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
            ->where('t.ecommerce_access', 1)
            ->where('b.shop_status', 'open')
            ->whereNotNull('b.latitude')
            ->whereNotNull('b.longitude')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('retail_inventory as ri')
                    ->whereColumn('ri.business_account', 'b.account')
                    ->where('ri.status', 'instock')
                    ->whereNotNull('ri.retail_price');
            })
            ->whereRaw("{$sql} <= ?", [...$bindings, $radius])
            ->select(['b.account', 'b.name'])
            ->selectRaw("{$sql} AS distance_m", $bindings)
            ->orderBy('distance_m')
            ->limit($limit)
            ->get()
            ->map(function ($shop) {
                $km = round($shop->distance_m / 1000, 1);
                $shop->distance_km = $km;
                $shop->eta_min = (int) (15 + ceil($km * 6));
                $shop->initial = mb_strtoupper(mb_substr(trim($shop->name), 0, 1));
                return $shop;
            });
    }

    /**
     * Is this shop within the maximum delivery radius of the buyer?
     */
    public function shopInRange(string $businessAccount, float $latitude, float $longitude): bool
    {
        [$sql, $bindings] = $this->distance($latitude, $longitude);

        return DB::connection('homemarket')
            ->table('business as b')
            ->where('b.account', $businessAccount)
            ->whereNotNull('b.latitude')
            ->whereNotNull('b.longitude')
            ->whereRaw("{$sql} <= ?", [...$bindings, config('homemarket.max_radius_m')])
            ->exists();
    }

    /**
     * Haversine distance in metres between the buyer and b.latitude/b.longitude.
     *
     * @return array{0: string, 1: array<int, float>} SQL and its three bindings
     */
    private function distance(float $latitude, float $longitude): array
    {
        $sql = '6371000 * ACOS(LEAST(1, GREATEST(-1,
                    COS(RADIANS(?)) * COS(RADIANS(b.latitude))
                    * COS(RADIANS(b.longitude) - RADIANS(?))
                    + SIN(RADIANS(?)) * SIN(RADIANS(b.latitude))
                )))';

        return [$sql, [$latitude, $longitude, $latitude]];
    }
}
