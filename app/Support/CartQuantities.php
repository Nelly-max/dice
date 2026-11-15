<?php

namespace App\Support;

use App\Models\Customer\Cart;

/**
 * What the current buyer already has in the cart, so item cards can show
 * "− 2 +" instead of "Add" (resources/views/<Shop>/products/items.blade.php).
 *
 * Read once per request (kept on the request) and keyed "<stockable_type>:<stockable_id>".
 */
class CartQuantities
{
    /** @return array<string, array{id:int, qty:int}> */
    public static function all(): array
    {
        $request = request();

        if ($request->attributes->has('cart_quantities')) {
            return $request->attributes->get('cart_quantities');
        }

        try {
            $map = Cart::forCurrent()
                ->get(['id', 'stockable_type', 'stockable_id', 'quantity'])
                ->mapWithKeys(fn ($c) => [
                    $c->stockable_type . ':' . $c->stockable_id => ['id' => (int) $c->id, 'qty' => (int) $c->quantity],
                ])
                ->all();
        } catch (\Throwable $e) {
            report($e);
            $map = [];
        }

        $request->attributes->set('cart_quantities', $map);

        return $map;
    }

    /** @return array{id:int, qty:int}|null */
    public static function for(string $stockableType, $stockableId): ?array
    {
        return self::all()[$stockableType . ':' . $stockableId] ?? null;
    }
}
