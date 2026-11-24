<?php

namespace App\Services\HomeMarket;

/**
 * Which Home Market shops and items a buyer may see (cart shop, delivery location,
 * radius). All of it lives in App\Services\Shop\ShopDiscovery, shared by the four
 * product shops; this class only names Home Market's database, stock type and columns.
 */
class ShopDiscovery extends \App\Services\Shop\ShopDiscovery
{
    protected const CONNECTION = 'homemarket';

    protected const STOCKABLE = 'retail_inventory';
}
