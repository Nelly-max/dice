<?php

namespace App\Services\HomeMarket;

/**
 * Turns a Home Market catalogue() row into what the views need. The shared work is in
 * App\Services\Shop\ProductPresenter (the four product shops use it).
 */
class ProductPresenter extends \App\Services\Shop\ProductPresenter
{
    protected const CONFIG = 'homemarket';
}
