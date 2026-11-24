<?php

namespace App\Services\LiquorHub;

/**
 * Turns a Liquor Hub catalogue() row into what the views need. The shared work is in
 * App\Services\Shop\ProductPresenter (the four product shops use it).
 */
class ProductPresenter extends \App\Services\Shop\ProductPresenter
{
    protected const CONFIG = 'liquorhub';

    protected function extras(object $row): void
    {
        $row->abv_label = $this->abvLabel($row->alcohol_percentage ?? null);
    }

    /**
     * "40% ABV", "4.5% ABV"; null when the product has no ABV captured.
     */
    public function abvLabel($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->trimNumber(number_format((float) $value, 2, '.', '')) . '% ABV';
    }
}
