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

    'product_image_prefix' => env('GLAMROOM_IMAGE_PREFIX', env('HOMEMARKET_IMAGE_PREFIX', '')),

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
        ['label' => 'Skincare',          'category_id' => null],
        ['label' => 'Makeup',            'category_id' => null],
        ['label' => 'Hair Care',         'category_id' => null],
        ['label' => 'Wigs & Extensions', 'category_id' => null],
        ['label' => 'Fragrance',         'category_id' => null],
        ['label' => 'Bath & Body',       'category_id' => null],
    ],

    /*
    | Image tiles under the hero slider. Files live in
    | {MEDIA_ROOT}/img/glamRoom/Category_icons.
    */

    // Add tiles once the icons are in {MEDIA_ROOT}/img/glamRoom/Category_icons, e.g.
    // ['label' => 'Skincare', 'file' => 'skincare.png', 'category_id' => 1],
    'category_tiles' => [],
];
