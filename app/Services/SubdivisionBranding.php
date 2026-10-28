<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Logo and name of the subdivision the buyer is currently in (looked up by
 * the first URL segment). Shared with every view, so it is cached and
 * remembered for the request rather than queried for each rendered partial.
 */
class SubdivisionBranding
{
    private array $memo = [];

    /** Subdivisions that live under their own route prefix rather than /{weblink}. */
    private const HOME_ROUTES = [
        'home-market' => 'homemarket.home',
    ];

    /** Subdivisions with their own footer partial; others get partials.shop-footer. */
    private const FOOTERS = [
        'home-market' => 'HomeMarket.partials.footer',
        'home-city'   => 'HomeCity.partials.footer',
        'gas'         => 'CookingGas.partials.footer',
    ];

    private ?array $weblinks = null;

    /**
     * @return array{logoUrl: string, subdivisionName: string}
     */
    public function forSegment(?string $segment): array
    {
        $key = $segment ?: '_';

        return $this->memo[$key] ??= Cache::remember(
            "subdivision.branding.{$key}",
            now()->addMinutes(10),
            fn () => $this->resolve($segment)
        );
    }

    private function resolve(?string $segment): array
    {
        try {
            $subdivision = DB::connection('hub')
                ->table('sub_divisions')
                ->where('weblink', $segment)
                ->first();      // all columns: the logo column is "logo" (older code used "image_path")

            $logo = $subdivision->logo ?? $subdivision->image_path ?? null;

            if ($subdivision && !empty($logo)) {
                return [
                    'logoUrl' => rtrim(config('app.media_url') ?: 'http://127.0.0.1:8000', '/')
                        . '/' . ltrim($logo, '/'),
                    'subdivisionName' => $subdivision->name,
                ];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'logoUrl' => asset('img/logo/homemarket.png'),
            'subdivisionName' => 'Marketplace',
        ];
    }

    /** Is this URL segment a subdivision (e.g. "home-market", "liquor-hub")? */
    public function isSubdivision(?string $segment): bool
    {
        if (!$segment) {
            return false;
        }

        return in_array(strtolower($segment), $this->weblinks(), true);
    }

    /**
     * Branding for the cart / checkout header: the subdivision the customer was
     * last shopping in, or Smart Market itself when they came from the main home page.
     *
     * @return array{name: string, logo: string, homeUrl: string, theme: string, weblink: ?string, footer: string}
     */
    public function shopping(?string $weblink): array
    {
        if ($weblink && $this->isSubdivision($weblink)) {
            $brand = $this->forSegment($weblink);
            $route = self::HOME_ROUTES[$weblink] ?? null;

            return [
                'name' => $brand['subdivisionName'] !== 'Marketplace'
                    ? $brand['subdivisionName']
                    : \Illuminate\Support\Str::headline($weblink),
                'logo' => $brand['logoUrl'],
                'homeUrl' => $route && \Illuminate\Support\Facades\Route::has($route) ? route($route) : url('/' . $weblink),
                'theme' => $weblink,
                'weblink' => $weblink,
                'footer' => self::FOOTERS[$weblink] ?? 'partials.shop-footer',
            ];
        }

        return [
            'name' => 'Smart Market',
            'logo' => asset('img/favicon.png'),
            'homeUrl' => route('home'),
            'theme' => 'smart-market',
            'weblink' => null,
            'footer' => 'partials.footer',
        ];
    }

    /** All subdivision weblinks (cached), plus the ones with their own route prefix. */
    private function weblinks(): array
    {
        return $this->weblinks ??= Cache::remember('subdivision.weblinks', now()->addMinutes(10), function () {
            $links = array_keys(self::HOME_ROUTES);

            try {
                $links = array_merge($links, DB::connection('hub')
                    ->table('sub_divisions')
                    ->whereNotNull('weblink')
                    ->pluck('weblink')
                    ->all());
            } catch (\Throwable $e) {
                report($e);
            }

            return array_values(array_unique(array_map('strtolower', array_filter($links))));
        });
    }
}
