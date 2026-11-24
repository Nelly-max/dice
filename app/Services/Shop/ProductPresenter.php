<?php

namespace App\Services\Shop;

use Carbon\Carbon;

/**
 * Turns a catalogue() row into what the views need: variant label, image URL,
 * live discount and distance.
 *
 * One copy for the four product shops (they were four near-identical files). Each shop's
 * class (App\Services\<Shop>\ProductPresenter) sets its config file and adds its own
 * labels in extras() (Liquor Hub: ABV, Farm Market: "/kg"); Glam Room adds the shade
 * in variantLabel().
 */
abstract class ProductPresenter
{
    /** config/<CONFIG>.php: product_image_prefix, placeholder_image */
    protected const CONFIG = 'homemarket';

    public function present(object $row): object
    {
        $row->variant_label = $this->variantLabel($row);
        $row->image_url = $this->imageUrl($row->image_path ?? null);
        $this->extras($row);

        $pricing = $this->pricing(
            (float) $row->retail_price,
            $row->discount ?? null,
            $row->discount_start ?? null,
            $row->discount_stop ?? null
        );

        $row->has_discount = $pricing['active'];
        $row->final_price = $pricing['final'];
        $row->discount_percentage = $pricing['percentage'];

        $row->distance_km = isset($row->distance_meters)
            ? round($row->distance_meters / 1000, 2)
            : null;

        return $row;
    }

    /**
     * "500ml", "2kg (6pcs)" ...
     */
    public function variantLabel(object $row): string
    {
        $label = '';

        if ($row->size_value && $row->quantity_unit) {
            $label = $this->trimNumber($row->size_value) . $row->quantity_unit;
        } elseif ($row->weight_value && $row->weight_unit) {
            $label = $this->trimNumber($row->weight_value) . $row->weight_unit;
        }

        if ($row->pieces) {
            $label .= " ({$row->pieces}pcs)";
        }

        return $label;
    }

    /** shop-specific labels on the row (none by default) */
    protected function extras(object $row): void
    {
    }

    public function imageUrl(?string $path): string
    {
        $base = rtrim(config('app.media_url'), '/');

        if (!empty($path)) {
            return $base . '/' . config(static::CONFIG . '.product_image_prefix') . ltrim($path, '/');
        }

        return $base . '/' . config(static::CONFIG . '.placeholder_image');
    }

    /**
     * `discount` is an amount (KES) taken off retail_price while the optional
     * start / stop window is open.
     *
     * @return array{active: bool, final: float, percentage: int}
     */
    public function pricing(float $price, $discount, $start, $stop): array
    {
        $none = ['active' => false, 'final' => $price, 'percentage' => 0];

        if (is_null($discount) || (float) $discount <= 0) {
            return $none;
        }

        $now = Carbon::now();

        if (($start && $now->lt(Carbon::parse($start))) || ($stop && $now->gt(Carbon::parse($stop)))) {
            return $none;
        }

        return [
            'active' => true,
            'final' => max(0, $price - (float) $discount),
            'percentage' => $price > 0 ? (int) round(((float) $discount / $price) * 100) : 0,
        ];
    }

    protected function trimNumber($value): string
    {
        $value = (string) $value;

        // "2.50" -> "2.5", "2.00" -> "2", but "100" stays "100".
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}
