<?php

namespace App\Http\Middleware;

use App\Services\SubdivisionBranding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The cart is shared by every subdivision, so the cart/checkout pages need to
 * know where the customer came from. Each page view inside a subdivision
 * (/home-market/..., /liquor-hub/...) remembers that subdivision; the Smart
 * Market home page (/) clears it. Everything else (cart, checkout, hub,
 * account, background requests) leaves it untouched.
 */
class RememberShoppingSubdivision
{
    public const KEY = 'shopping.subdivision';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('GET')
            || !$request->hasSession()
            || $request->expectsJson()
            || $request->header('X-Requested-With') === 'XMLHttpRequest'
            || !$response->isSuccessful()) {
            return $response;
        }

        $segment = $request->segment(1);

        if ($segment === null) {
            $request->session()->forget(self::KEY);
        } elseif (app(SubdivisionBranding::class)->isSubdivision($segment)) {
            $request->session()->put(self::KEY, $segment);
        }

        return $response;
    }
}
