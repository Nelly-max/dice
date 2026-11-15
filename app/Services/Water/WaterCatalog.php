<?php

namespace App\Services\Water;

use App\Models\Customer\Cart;
use App\Models\Water\Inventory;
use App\Services\DeliveryLocationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which water shops a buyer may see — the same rules as Home Market and Cooking Gas:
 *
 *   1. the shop's subscription is active (matched on the main branch, so branches count too)
 *      and its tariff has e-commerce (tariffs.ecommerce_access = 1)
 *   2. the shop is open (business.shop_status = 'open')
 *   3. once the cart holds water from a shop, only that shop (one water shop per cart)
 *   4. otherwise, with a saved delivery location, only shops within the delivery radius
 *      (config water.max_radius_m); without a location, every shop
 *
 * Inventory::scopeSellable() applies these to the price list. Worked out once per request.
 */
class WaterCatalog
{
    public function __construct(private DeliveryLocationService $location)
    {
    }

    /**
     * @return array{location: ?array, shops: Collection, inRange: Collection, visibleAccounts: array<string>,
     *               cartShop: ?object, cartAccount: ?string, outOfRange: bool, radius: int}
     */
    public function context(): array
    {
        $request = request();

        if ($request->attributes->has('water_catalog')) {
            return $request->attributes->get('water_catalog');
        }

        $location = $this->location->get($request);
        $radius = $this->radius();

        $shops = $this->eligibleShops()->map(function ($s) use ($location) {
            $s->distance_m = $location ? $this->metres($location, $s) : null;
            return $s;
        });

        try {
            $cartAccount = Cart::forCurrent()
                ->whereIn('stockable_type', Inventory::TYPES)
                ->whereNotNull('business_account')
                ->value('business_account');
        } catch (\Throwable $e) {
            report($e);
            $cartAccount = null;
        }

        $cartShop = $cartAccount ? $shops->firstWhere('account', $cartAccount) : null;
        $inRange = $location
            ? $shops->filter(fn ($s) => $s->distance_m !== null && $s->distance_m <= $radius)
            : $shops;

        $visible = $cartShop ? collect([$cartShop]) : $inRange;

        $context = [
            'location' => $location,
            'shops' => $shops,
            'inRange' => $inRange->sortBy(fn ($s) => $s->distance_m ?? PHP_INT_MAX)->values(),
            'visibleAccounts' => $visible->pluck('account')->map(fn ($a) => (string) $a)->values()->all(),
            'cartShop' => $cartShop,
            'cartAccount' => $cartAccount,
            'outOfRange' => $location && !$cartShop && $inRange->isEmpty(),
            'radius' => $radius,
        ];

        $request->attributes->set('water_catalog', $context);

        return $context;
    }

    /** Accounts of the shops this buyer may see (rules 1–4). */
    public function visibleAccounts(): array
    {
        return $this->context()['visibleAccounts'];
    }

    public function radius(): int
    {
        return (int) config('water.max_radius_m', config('homemarket.max_radius_m', 3000));
    }

    /** Add-to-cart check (CartController): the shop is eligible and within the delivery radius. */
    public function shopInRange(string $account, float $latitude, float $longitude): bool
    {
        $shop = $this->eligibleShops()->firstWhere('account', $account);
        if (!$shop) return false;

        $m = $this->metres(['latitude' => $latitude, 'longitude' => $longitude], $shop);

        return $m !== null && $m <= $this->radius();
    }

    /** Rules 1 and 2: subscribed with e-commerce, and open. */
    private function eligibleShops(): Collection
    {
        $request = request();
        if ($request->attributes->has('water_shops')) {
            return $request->attributes->get('water_shops')->map(fn ($s) => clone $s);
        }

        try {
            $cache = DB::connection('water')
                ->table('business as b')
                ->join('business_subscription as bs', function ($join) {
                    $join->on('bs.main_branch_account', '=', DB::raw('COALESCE(b.main_branch_account, b.account)'))
                        ->where('bs.status', 'active');
                })
                ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
                ->where('t.ecommerce_access', 1)
                ->where('b.shop_status', 'open')
                ->distinct()
                ->get(['b.id', 'b.account', 'b.name', 'b.latitude', 'b.longitude']);
        } catch (\Throwable $e) {
            report($e);
            $cache = collect();
        }

        $request->attributes->set('water_shops', $cache);

        return $cache->map(fn ($s) => clone $s);
    }

    /** Straight-line metres from the delivery location to a shop (null without a pin). */
    public function metres(array $loc, $shop): ?int
    {
        if (!is_numeric($shop->latitude ?? null) || !is_numeric($shop->longitude ?? null)) return null;

        $r = 6371000;
        $dLat = deg2rad($shop->latitude - $loc['latitude']);
        $dLng = deg2rad($shop->longitude - $loc['longitude']);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($loc['latitude'])) * cos(deg2rad($shop->latitude)) * sin($dLng / 2) ** 2;

        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
