<?php

/*
|--------------------------------------------------------------------------
| Water
|--------------------------------------------------------------------------
|
| Products come from the water database (connection 'water'): each shop's
| `inventory` row is one container with a refill_price and/or sale_price.
| Shops show under the same rules as Home Market (App\Services\Water\WaterCatalog).
|
| The options below are the header strip pages, the home page rows and the
| cart stockable types (config/stockables.php).
|
*/

return [

    // delivery radius from the buyer's saved location
    'max_radius_m' => env('WATER_MAX_RADIUS_M', 3000),

    'delivery_eta' => '30 – 90 mins',

    // containers this big (or named truck / tanker / bowser) are clean water by truck
    'truck_min_litres' => 1000,

    // most of one item per order (water has no stock count)
    'max_per_item' => 99,

    'options' => [
        'refill' => [
            'label' => 'Purified water refill',
            'short' => 'Refills',
            'icon' => 'fa-solid fa-rotate',
            'type' => 'water_refill',
            'route' => 'water.refill',
            'intro' => 'Your empty bottle refilled with purified water and brought back to you.',
            'card' => 'refill',
        ],
        'bottled' => [
            'label' => 'Bottled water',
            'short' => 'Bottled',
            'icon' => 'fa-solid fa-bottle-water',
            'type' => 'water_bottled',
            'route' => 'water.bottled',
            'intro' => 'New, sealed bottles of water from shops near you.',
            'card' => 'new bottle',
        ],
        'truck' => [
            'label' => 'Clean water',
            'short' => 'Water trucks',
            'icon' => 'fa-solid fa-truck-droplet',
            'type' => 'water_truck',
            'route' => 'water.clean',
            'intro' => 'Clean water delivered by truck to your estate, home or site tank.',
            'card' => 'per trip',
        ],
    ],

];
