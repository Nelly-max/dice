<?php

namespace App\Services\GlamRoom;

/**
 * Turns a Glam Room catalogue() row into what the views need. The shared work is in
 * App\Services\Shop\ProductPresenter (the four product shops use it).
 */
class ProductPresenter extends \App\Services\Shop\ProductPresenter
{
    protected const CONFIG = 'glamroom';

    /**
     * "50ml · Ivory": the shared label plus the makeup shade / hair colour.
     */
    public function variantLabel(object $row): string
    {
        $label = parent::variantLabel($row);

        if (!empty($row->shade)) {
            $label = trim($label . ' · ' . $row->shade, ' ·');
        }

        return $label;
    }
}
