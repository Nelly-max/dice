<?php

namespace App\Services\Cart;

use App\Models\Customer\Cart;
use App\Models\Customer\ItemReservation;
use App\Models\CookingGas\BusinessGasInventory;
use App\Models\HomeMarket\RetailInventory;
use Illuminate\Support\Collection;

/**
 * Cart stock and the short hold on what a buyer adds.
 *
 * - Adding an item (or changing its quantity) holds that quantity for the buyer for
 *   config smartmarket.cart_hold_minutes (item_reservations): other buyers can't take it.
 * - When the hold runs out the quantity is released, but the item stays in the cart
 *   (like Jumia / Carrefour). Coming back to the cart or checkout holds it again if it's
 *   still free.
 * - An item leaves the cart only when the shop has none left. If the shop has fewer than
 *   the cart asks for, the quantity is brought down to what the shop has.
 * - Stock held by other buyers is not "gone": the item stays, marked as waiting for it.
 */
class CartStock
{
    public static function holdMinutes(): int
    {
        return max(1, (int) config('smartmarket.cart_hold_minutes', 10));
    }

    /** What the shop has right now (business side), whatever is held. */
    public function stock($stockable): int
    {
        if (!$stockable) return 0;

        // models that know their own stock (Water: made to order, no stock column)
        if ($stockable instanceof \App\Models\Water\Inventory) return (int) $stockable->availableStock();
        if ($stockable instanceof BusinessGasInventory) return (int) ($stockable->filled_cylinders ?? 0);
        if ($stockable instanceof RetailInventory) return (int) ($stockable->stock_available ?? 0);
        if (isset($stockable->stock_available)) return (int) $stockable->stock_available;

        return 0;
    }

    /** Quantity other buyers are holding right now. */
    public function heldByOthers(string $type, int $id, ?int $exceptCartId = null): int
    {
        return (int) ItemReservation::query()
            ->where('stockable_type', $type)
            ->where('stockable_id', $id)
            ->when($exceptCartId, fn ($q) => $q->where('cart_id', '!=', $exceptCartId))
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    /** What this cart row may have: the shop's stock less what others hold. */
    public function free(Cart $item): int
    {
        return max(0, $this->stock($item->stockable) - $this->heldByOthers($item->stockable_type, (int) $item->stockable_id, $item->id));
    }

    /** Hold a cart row's quantity (or the part of it that is free) for another few minutes. */
    public function hold(Cart $item, ?int $quantity = null): int
    {
        $want = $quantity ?? (int) $item->quantity;
        $qty = min($want, $this->free($item));

        if ($qty <= 0) {
            ItemReservation::where('cart_id', $item->id)->delete();
            return 0;
        }

        ItemReservation::updateOrCreate(
            ['cart_id' => $item->id],
            [
                'stockable_type' => $item->stockable_type,
                'stockable_id' => $item->stockable_id,
                'quantity' => $qty,
                'expires_at' => now()->addMinutes(self::holdMinutes()),
                'updated_at' => now(),
            ]
        );

        return $qty;
    }

    /**
     * Bring the cart in line with the shops' stock, and (with $hold) hold it again.
     *
     * Returns:
     *   removed   [name, ...]                    sold out, taken out of the cart
     *   reduced   [[name, from, to], ...]        the shop has fewer, quantity lowered
     *   waiting   [cart_id => [name, qty]]       qty of the row is held by other buyers
     *   held      [cart_id => qty]               held for this buyer
     *   holdUntil Carbon|null                    when this buyer's holds end (earliest)
     *   changed   bool                           anything removed or lowered
     */
    public function sync(Collection $items, bool $hold = true): array
    {
        $out = ['removed' => [], 'reduced' => [], 'waiting' => [], 'held' => [], 'holdUntil' => null, 'changed' => false];

        foreach ($items as $item) {
            $name = $item->stockable ? $item->product_name : 'An item';
            $stock = $this->stock($item->stockable);

            // sold out (or the product is gone): out of the cart
            if ($stock <= 0) {
                ItemReservation::where('cart_id', $item->id)->delete();
                $item->delete();
                $out['removed'][] = $name;
                $out['changed'] = true;
                continue;
            }

            // the shop has fewer than the cart asks for
            if ($item->quantity > $stock) {
                $out['reduced'][] = [$name, (int) $item->quantity, $stock];
                $item->quantity = $stock;
                $item->save();
                $out['changed'] = true;
            }

            $held = $hold
                ? $this->hold($item)
                : (int) ItemReservation::where('cart_id', $item->id)->where('expires_at', '>', now())->value('quantity');
            $out['held'][$item->id] = $held;

            // part of it is in other buyers' carts right now (their hold runs out in a few minutes)
            $short = (int) $item->quantity - $this->free($item);
            if ($short > 0) {
                $out['waiting'][$item->id] = [$name, $short];
            }
        }

        if ($out['held'] && array_sum($out['held']) > 0) {
            $out['holdUntil'] = ItemReservation::whereIn('cart_id', array_keys(array_filter($out['held'])))
                ->where('expires_at', '>', now())
                ->min('expires_at');
            $out['holdUntil'] = $out['holdUntil'] ? \Illuminate\Support\Carbon::parse($out['holdUntil']) : null;
        }

        return $out;
    }

    /** A short fingerprint of the cart ("id:qty,..."), so the cart page can tell it changed. */
    public static function signature(Collection $items): string
    {
        return $items->sortBy('id')->map(fn ($i) => $i->id . ':' . (int) $i->quantity)->implode(',');
    }
}
