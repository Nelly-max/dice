<?php

return [

    'cooking_gas_stock' => App\Models\CookingGas\BusinessGasInventory::class,

    'retail_inventory' => App\Models\HomeMarket\RetailInventory::class,

    // Liquor Hub shares Home Market's structure but lives in its own database,
    // so its inventory needs its own morph alias.
    'liquor_inventory' => App\Models\LiquorHub\RetailInventory::class,

    // Glam Room (beauty) - same structure, own database.
    'glam_inventory' => App\Models\GlamRoom\RetailInventory::class,

    // Farm Market (fresh produce) - same structure, own database.
    'farm_inventory' => App\Models\FarmMarket\RetailInventory::class,

    // Water: one inventory row, sold as a refill, a new bottle or a truck delivery.
    // The option is the stockable type, so each is its own cart line and price.
    'water_refill' => App\Models\Water\WaterRefill::class,
    'water_bottled' => App\Models\Water\WaterBottled::class,
    'water_truck' => App\Models\Water\WaterTruck::class,

];