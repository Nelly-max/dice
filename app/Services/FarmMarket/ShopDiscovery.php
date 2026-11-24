<?php

namespace App\Services\FarmMarket;

/**
 * Which Farm Market shops and items a buyer may see (cart shop, delivery location,
 * radius). All of it lives in App\Services\Shop\ShopDiscovery, shared by the four
 * product shops; this class only names Farm Market's database, stock type and columns.
 */
class ShopDiscovery extends \App\Services\Shop\ShopDiscovery
{
    protected const CONNECTION = 'farmmarket';

    protected const STOCKABLE = 'farm_inventory';

    protected const EXTRA_COLUMNS = ['p.is_organic', 'pi.sell_by', 'pi.approx_pieces'];
}
