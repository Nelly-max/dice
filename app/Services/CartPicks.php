<?php

namespace App\Services;

use App\Services\HomeMarket\ProductPresenter;
use App\Services\HomeMarket\ShopDiscovery;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * "Popular picks" under the empty cart: items from shops whose tariff includes
 * premium listing, per subdivision.
 *
 * The cart is shared by all subdivisions, so the rows follow where the customer
 * was shopping (RememberShoppingSubdivision):
 *   - in a subdivision      -> that subdivision's premium items only
 *   - on Smart Market home  -> a row for every subdivision that has picks
 *
 * To add a subdivision, add its weblink to PROVIDERS and write a method that
 * returns presented items (see homeMarket()). Subdivisions without a provider
 * simply show no row.
 */
class CartPicks
{
    private const PROVIDERS = [
        'home-market' => 'homeMarket',
    ];

    private const LIMIT = 12;

    public function __construct(private SubdivisionBranding $branding)
    {
    }

    /**
     * @return Collection<int, array{title: string, name: string, homeUrl: string, products: Collection}>
     */
    public function forShopping(?string $weblink, Request $request): Collection
    {
        $weblinks = $weblink ? [$weblink] : array_keys(self::PROVIDERS);

        return collect($weblinks)
            ->filter(fn ($link) => isset(self::PROVIDERS[$link]))
            ->map(function ($link) use ($request, $weblink) {
                $brand = $this->branding->shopping($link);

                try {
                    $products = $this->{self::PROVIDERS[$link]}($request);
                } catch (\Throwable $e) {
                    report($e);
                    $products = collect();
                }

                return [
                    // inside a subdivision the brand is already in the header
                    'title' => $weblink ? 'Popular picks' : 'Popular on ' . $brand['name'],
                    'name' => $brand['name'],
                    'homeUrl' => $brand['homeUrl'],
                    'products' => $products,
                ];
            })
            ->filter(fn ($row) => $row['products']->isNotEmpty())
            ->values();
    }

    /** HomeMarket: premium-listing shops the buyer can order from (same radius rules as the home page). */
    private function homeMarket(Request $request): Collection
    {
        $shops = app(ShopDiscovery::class);
        $presenter = app(ProductPresenter::class);

        $context = $shops->context($request);

        return $shops->restrict($shops->catalogue(), $context)
            ->where('t.premium_listing', 1)
            ->inRandomOrder()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($row) => $presenter->present($row));
    }
}
