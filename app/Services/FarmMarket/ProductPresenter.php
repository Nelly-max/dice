<?php

namespace App\Services\FarmMarket;

/**
 * Turns a Farm Market catalogue() row into what the views need. The shared work is in
 * App\Services\Shop\ProductPresenter (the four product shops use it).
 */
class ProductPresenter extends \App\Services\Shop\ProductPresenter
{
    protected const CONFIG = 'farmmarket';

    protected function extras(object $row): void
    {
        $row->per_label = $this->perLabel($row);
    }

    /**
     * Farm Market: what the price is for - "/kg", "/500 g", "/bunch", "/30 pcs".
     */
    public function perLabel(object $row): ?string
    {
        $unit = $row->quantity_unit ?? null;

        if ($unit && $row->size_value) {
            $size = $this->trimNumber($row->size_value);

            return '/' . ($size === '1' ? $unit : "{$size} {$unit}");
        }

        if (!empty($row->pieces)) {
            return $row->pieces > 1 ? "/{$row->pieces} pcs" : '/pc';
        }

        return null;
    }
}
