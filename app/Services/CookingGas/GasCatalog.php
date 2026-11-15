<?php

namespace App\Services\CookingGas;

use App\Models\Customer\Cart;
use App\Services\DeliveryLocationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Which gas shops a buyer may see — the same rules as Home Market's ShopDiscovery:
 *
 *   1. the shop's subscription is active (matched on the main branch, so branches count too)
 *      and its tariff has e-commerce (tariffs.ecommerce_access = 1)
 *   2. the shop is open (business.shop_status = 'open')
 *   3. once the cart holds gas from a shop, only that shop (one gas shop per cart)
 *   4. otherwise, with a saved delivery location, only shops within the delivery radius
 *      (config homemarket.max_radius_m); without a location, every shop
 *
 * Refills must also be in stock (filled_cylinders > 0) — BusinessGasInventory::scopeSellable().
 * Worked out once per request.
 */
class GasCatalog
{
    public function __construct(private DeliveryLocationService $location)
    {
    }

    /**
     * @return array{location: ?array, shops: Collection, visibleIds: array<int>, cartShop: ?object,
     *               outOfRange: bool, radius: int}
     */
    public function context(): array
    {
        $request = request();

        if ($request->attributes->has('gas_catalog')) {
            return $request->attributes->get('gas_catalog');
        }

        $location = $this->location->get($request);
        $radius = (int) config('homemarket.max_radius_m', 3000);

        $shops = $this->eligibleShops()->map(function ($s) use ($location) {
            $s->distance_m = $location ? $this->metres($location, $s) : null;
            return $s;
        });

        $cartAccount = Cart::forCurrent()
            ->where('stockable_type', 'cooking_gas_stock')
            ->whereNotNull('business_account')
            ->value('business_account');

        $cartShop = $cartAccount ? $shops->firstWhere('account', $cartAccount) : null;
        $inRange = $location
            ? $shops->filter(fn ($s) => $s->distance_m !== null && $s->distance_m <= $radius)
            : $shops;

        $visible = $cartShop ? collect([$cartShop]) : $inRange;

        $context = [
            'location' => $location,
            'shops' => $shops,
            'inRange' => $inRange->sortBy(fn ($s) => $s->distance_m ?? PHP_INT_MAX)->values(),
            'visibleIds' => $visible->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'cartShop' => $cartShop,
            'cartAccount' => $cartAccount,
            'outOfRange' => $location && !$cartShop && $inRange->isEmpty(),
            'radius' => $radius,
        ];

        $request->attributes->set('gas_catalog', $context);

        return $context;
    }

    /** Ids of the shops this buyer may see (rules 1–4). */
    public function visibleIds(): array
    {
        return $this->context()['visibleIds'];
    }

    /** Rules 1 and 2: subscribed with e-commerce, and open. */
    private function eligibleShops(): Collection
    {
        try {
            return DB::connection('cookinggas')
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
            return collect();
        }
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
