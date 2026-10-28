<?php

namespace App\Services\HomeMarket;

use Carbon\Carbon;

/**
 * Turns a catalogue() row into what the views need: variant label, image URL,
 * live discount and distance. The single home for logic that used to be
 * copied into the home page, the item page and its variations.
 */
class ProductPresenter
{
    public function present(object $row): object
    {
        $row->variant_label = $this->variantLabel($row);
        $row->image_url = $this->imageUrl($row->image_path ?? null);

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

    public function imageUrl(?string $path): string
    {
        $base = rtrim(config('app.media_url'), '/');

        if (!empty($path)) {
            return $base . '/' . config('homemarket.product_image_prefix') . ltrim($path, '/');
        }

        return $base . '/' . config('homemarket.placeholder_image');
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

    private function trimNumber($value): string
    {
        $value = (string) $value;

        // "2.50" -> "2.5", "2.00" -> "2", but "100" stays "100".
        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}
