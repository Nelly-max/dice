<?php

namespace App\Services\LiquorHub;

/**
 * Which Liquor Hub shops and items a buyer may see (cart shop, delivery location,
 * radius). All of it lives in App\Services\Shop\ShopDiscovery, shared by the four
 * product shops; this class only names Liquor Hub's database, stock type and columns.
 */
class ShopDiscovery extends \App\Services\Shop\ShopDiscovery
{
    protected const CONNECTION = 'liquorhub';

    protected const STOCKABLE = 'liquor_inventory';

    protected const EXTRA_COLUMNS = ['p.alcohol_percentage'];
}
