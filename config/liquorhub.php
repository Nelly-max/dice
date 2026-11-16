<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Delivery radius (metres)
    |--------------------------------------------------------------------------
    |
    | Shops are searched in these steps; the first step that contains a
    | qualifying shop is used. The last step is the hard maximum: nothing
    | further than this from the buyer is shown or can be added to the cart.
    |
    */

    'radius_steps_m' => [1000, 2000, 3000],

    'max_radius_m' => 3000,

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | product_image_prefix is put between MEDIA_URL and the image_path stored
    | in product_item_images. Leave it empty if MEDIA_URL already ends in
    | /media, otherwise set it to 'media/'.
    |
    */

    'product_image_prefix' => env('LIQUORHUB_IMAGE_PREFIX', env('HOMEMARKET_IMAGE_PREFIX', '')),

    'placeholder_image' => 'img/homeMarket/products/item_image.png',

    'gallery_cache_seconds' => 300,

    /*
    |--------------------------------------------------------------------------
    | Home page
    |--------------------------------------------------------------------------
    */

    'home_item_limit' => 120,

    'row_size' => 20,

    /*
    | Sub-category links shown in the navigation bar.
    | category_id is products.category_id. Leave it null until you have the
    | id; the link is then disabled.
    */

    'nav_categories' => [
        ['label' => 'Whisky',   'category_id' => null],
        ['label' => 'Vodka',    'category_id' => null],
        ['label' => 'Gin',      'category_id' => null],
        ['label' => 'Wine',     'category_id' => null],
        ['label' => 'Beer',     'category_id' => null],
        ['label' => 'Mixers',   'category_id' => null],
    ],

    /*
    | Image tiles under the hero slider. Files live in
    | {MEDIA_ROOT}/img/liquorHub/Category_icons.
    */

    // Add tiles once the icons are in {MEDIA_ROOT}/img/liquorHub/Category_icons, e.g.
    // ['label' => 'Whisky', 'file' => 'whisky.png', 'category_id' => 3],
    'category_tiles' => [],
];
