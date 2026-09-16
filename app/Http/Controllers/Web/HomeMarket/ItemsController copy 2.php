<?php

namespace App\Http\Controllers\Web\HomeMarket;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Customer\Cart;

class ItemsController extends Controller
{
    /**
     * Display all items on the homepage.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Get Current Customer
        |--------------------------------------------------------------------------
        */

        $userId = auth('customer')->id();
        $sessionId = $request->session()->getId();


        /*
        |--------------------------------------------------------------------------
        | Get Current Customer Cart Shop
        |--------------------------------------------------------------------------
        |
        | The first shop represented in the active cart becomes the
        | selected shop.
        |
        */

        $cartQuery = Cart::active();

        if ($userId) {

            $cartQuery->where(
                'user_id',
                $userId
            );

        } else {

            $cartQuery->where(
                'session_id',
                $sessionId
            );
        }

        $selectedBusinessAccount = $cartQuery
            ->whereNotNull('business_account')
            ->value('business_account');


        /*
        |--------------------------------------------------------------------------
        | Customer Delivery Coordinates
        |--------------------------------------------------------------------------
        |
        | These may come from:
        |
        | /home-market?latitude=-1.3812&longitude=36.7881
        |
        */

        $customerLatitude = $request->query('latitude');
        $customerLongitude = $request->query('longitude');

        $hasDeliveryLocation =
            is_numeric($customerLatitude) &&
            is_numeric($customerLongitude) &&
            (float) $customerLatitude >= -90 &&
            (float) $customerLatitude <= 90 &&
            (float) $customerLongitude >= -180 &&
            (float) $customerLongitude <= 180;

        if ($hasDeliveryLocation) {

            $customerLatitude = (float) $customerLatitude;
            $customerLongitude = (float) $customerLongitude;

        } else {

            $customerLatitude = null;
            $customerLongitude = null;
        }


        /*
        |--------------------------------------------------------------------------
        | Progressive Delivery Radius
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | If a cart shop already exists, we DO NOT use this radius to
        | select other shops.
        |
        | The cart shop always has priority.
        |
        */

        $selectedRadius = null;

        if (
            $hasDeliveryLocation &&
            !$selectedBusinessAccount
        ) {

            $radiusOptions = [
                500,
                1000,
                2000,
                3000,
            ];

            foreach ($radiusOptions as $radius) {

                /*
                |--------------------------------------------------------------------------
                | Haversine Distance
                |--------------------------------------------------------------------------
                */

                $distanceSql = "
                    6371000 * ACOS(
                        LEAST(
                            1,
                            GREATEST(
                                -1,
                                COS(RADIANS(?))
                                *
                                COS(RADIANS(b.latitude))
                                *
                                COS(
                                    RADIANS(b.longitude)
                                    -
                                    RADIANS(?)
                                )
                                +
                                SIN(RADIANS(?))
                                *
                                SIN(RADIANS(b.latitude))
                            )
                        )
                    )
                ";


                /*
                |--------------------------------------------------------------------------
                | Check For Qualifying Shop
                |--------------------------------------------------------------------------
                */

                $hasShop = DB::connection('homemarket')
                    ->table('business as b')

                    /*
                    |--------------------------------------------------------------------------
                    | Active Subscription
                    |--------------------------------------------------------------------------
                    */

                    ->join(
                        'business_subscription as bs',
                        function ($join) {

                            $join->on(
                                'bs.main_branch_account',
                                '=',
                                'b.main_branch_account'
                            );

                            $join->where(
                                'bs.status',
                                'active'
                            );
                        }
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Tariff
                    |--------------------------------------------------------------------------
                    */

                    ->join(
                        'tariffs as t',
                        't.id',
                        '=',
                        'bs.tariff_id'
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Qualifying Inventory
                    |--------------------------------------------------------------------------
                    */

                    ->whereExists(function ($query) {

                        $query
                            ->select(DB::raw(1))
                            ->from('retail_inventory as ri')
                            ->whereColumn(
                                'ri.business_account',
                                'b.account'
                            )
                            ->where(
                                'ri.status',
                                'instock'
                            )
                            ->whereNotNull(
                                'ri.retail_price'
                            );
                    })

                    /*
                    |--------------------------------------------------------------------------
                    | Ecommerce Access
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        't.ecommerce_access',
                        1
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Shop Open
                    |--------------------------------------------------------------------------
                    */

                    ->where(
                        'b.shop_status',
                        'open'
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Coordinates
                    |--------------------------------------------------------------------------
                    */

                    ->whereNotNull(
                        'b.latitude'
                    )
                    ->whereNotNull(
                        'b.longitude'
                    )

                    /*
                    |--------------------------------------------------------------------------
                    | Radius
                    |--------------------------------------------------------------------------
                    */

                    ->whereRaw(
                        "{$distanceSql} <= ?",
                        [
                            $customerLatitude,
                            $customerLongitude,
                            $customerLatitude,
                            $radius,
                        ]
                    )

                    ->exists();


                /*
                |--------------------------------------------------------------------------
                | First Matching Radius
                |--------------------------------------------------------------------------
                */

                if ($hasShop) {

                    $selectedRadius = $radius;

                    break;
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Products Query
        |--------------------------------------------------------------------------
        */

        $products = DB::connection('homemarket')
            ->table('retail_inventory as ri')

            /*
            |--------------------------------------------------------------------------
            | Business
            |--------------------------------------------------------------------------
            */

            ->join(
                'business as b',
                'b.account',
                '=',
                'ri.business_account'
            )

            /*
            |--------------------------------------------------------------------------
            | Active Subscription
            |--------------------------------------------------------------------------
            */

            ->join(
                'business_subscription as bs',
                function ($join) {

                    $join->on(
                        'bs.main_branch_account',
                        '=',
                        'b.main_branch_account'
                    );

                    $join->where(
                        'bs.status',
                        'active'
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Tariff
            |--------------------------------------------------------------------------
            */

            ->join(
                'tariffs as t',
                't.id',
                '=',
                'bs.tariff_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            ->join(
                'products as p',
                'p.id',
                '=',
                'ri.product_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Product Item
            |--------------------------------------------------------------------------
            */

            ->join(
                'product_items as pi',
                'pi.id',
                '=',
                'ri.item_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Packaging
            |--------------------------------------------------------------------------
            */

            ->leftJoin(
                'product_variables.product_packaging as pv_pack',
                'pv_pack.id',
                '=',
                'pi.packaging_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Quantity Unit
            |--------------------------------------------------------------------------
            */

            ->leftJoin(
                'product_variables.quantity_units as qu',
                'qu.id',
                '=',
                'pi.quantity_unit_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Weight Unit
            |--------------------------------------------------------------------------
            */

            ->leftJoin(
                'product_variables.weight_units as wu',
                'wu.id',
                '=',
                'pi.weight_unit_id'
            )

            /*
            |--------------------------------------------------------------------------
            | Basic Product Eligibility
            |--------------------------------------------------------------------------
            */

            ->where(
                'bs.status',
                'active'
            )

            ->where(
                't.ecommerce_access',
                1
            )

            ->where(
                'b.shop_status',
                'open'
            )

            ->where(
                'ri.status',
                'instock'
            )

            ->whereNotNull(
                'ri.retail_price'
            );


        /*
        |--------------------------------------------------------------------------
        | SHOP SELECTION PRIORITY
        |--------------------------------------------------------------------------
        |
        | 1. If cart already has a shop:
        |       ONLY THAT SHOP.
        |
        | 2. If cart is empty but delivery location exists:
        |       Apply progressive radius.
        |
        | 3. If neither exists:
        |       Show all qualifying shops.
        |
        */


        /*
        |--------------------------------------------------------------------------
        | PRIORITY 1:
        | Existing Cart Shop
        |--------------------------------------------------------------------------
        */

        if ($selectedBusinessAccount) {

            /*
            |--------------------------------------------------------------------------
            | CRITICAL SHOP LOCK
            |--------------------------------------------------------------------------
            |
            | Once the first item is added to the cart, every product
            | displayed on HomeMarket must belong to that same shop.
            |
            */

            $products->where(
                'ri.business_account',
                $selectedBusinessAccount
            );


            /*
            |--------------------------------------------------------------------------
            | IMPORTANT:
            | Do NOT apply delivery-radius filtering here.
            |--------------------------------------------------------------------------
            |
            | The cart shop has already been selected.
            |
            */

        }


        /*
        |--------------------------------------------------------------------------
        | PRIORITY 2:
        | No Cart Shop + Delivery Location
        |--------------------------------------------------------------------------
        */

        elseif ($hasDeliveryLocation) {

            /*
            |--------------------------------------------------------------------------
            | No Qualifying Shop Within 3km
            |--------------------------------------------------------------------------
            */

            if (is_null($selectedRadius)) {

                $products->whereRaw(
                    '1 = 0'
                );

            } else {

                /*
                |--------------------------------------------------------------------------
                | Haversine Distance
                |--------------------------------------------------------------------------
                */

                $distanceSql = "
                    6371000 * ACOS(
                        LEAST(
                            1,
                            GREATEST(
                                -1,
                                COS(RADIANS(?))
                                *
                                COS(RADIANS(b.latitude))
                                *
                                COS(
                                    RADIANS(b.longitude)
                                    -
                                    RADIANS(?)
                                )
                                +
                                SIN(RADIANS(?))
                                *
                                SIN(RADIANS(b.latitude))
                            )
                        )
                    )
                ";

                $products
                    ->whereNotNull(
                        'b.latitude'
                    )
                    ->whereNotNull(
                        'b.longitude'
                    )

                    ->whereRaw(
                        "{$distanceSql} <= ?",
                        [
                            $customerLatitude,
                            $customerLongitude,
                            $customerLatitude,
                            $selectedRadius,
                        ]
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PRIORITY 3:
        | No Cart Shop + No Delivery Location
        |--------------------------------------------------------------------------
        |
        | No additional business filter is applied.
        |
        */


        /*
        |--------------------------------------------------------------------------
        | Select Product Attributes
        |--------------------------------------------------------------------------
        */

        $products->select([

            /*
            |--------------------------------------------------------------------------
            | Retail Inventory
            |--------------------------------------------------------------------------
            */

            'ri.*',

            /*
            |--------------------------------------------------------------------------
            | Business
            |--------------------------------------------------------------------------
            */

            'b.name as business_name',
            'b.account as business_account',
            'b.latitude as business_latitude',
            'b.longitude as business_longitude',

            /*
            |--------------------------------------------------------------------------
            | Product
            |--------------------------------------------------------------------------
            */

            'p.id as product_id',
            'p.code as product_code',
            'p.name as product_name',
            'p.brand',
            'p.description',

            /*
            |--------------------------------------------------------------------------
            | Product Item
            |--------------------------------------------------------------------------
            */

            'pi.id as item_id',
            'pi.code as item_code',
            'pi.size_value',
            'pi.weight_value',
            'pi.pieces',

            /*
            |--------------------------------------------------------------------------
            | Units
            |--------------------------------------------------------------------------
            */

            'qu.slug as quantity_unit',
            'wu.name as weight_unit',

            /*
            |--------------------------------------------------------------------------
            | Packaging
            |--------------------------------------------------------------------------
            */

            'pv_pack.name as packaging_name',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Product Image
        |--------------------------------------------------------------------------
        */

        $products->selectRaw("
            (
                SELECT pii.image_path
                FROM product_item_images as pii
                WHERE pii.product_item_id = pi.id
                ORDER BY
                    pii.is_primary DESC,
                    pii.id ASC
                LIMIT 1
            ) AS image_path
        ");


        /*
        |--------------------------------------------------------------------------
        | Add Distance To Products
        |--------------------------------------------------------------------------
        |
        | Only relevant when delivery location is being used to
        | discover shops.
        |
        */

        if (
            $hasDeliveryLocation &&
            !$selectedBusinessAccount
        ) {

            $distanceSql = "
                6371000 * ACOS(
                    LEAST(
                        1,
                        GREATEST(
                            -1,
                            COS(RADIANS(?))
                            *
                            COS(RADIANS(b.latitude))
                            *
                            COS(
                                RADIANS(b.longitude)
                                -
                                RADIANS(?)
                            )
                            +
                            SIN(RADIANS(?))
                            *
                            SIN(RADIANS(b.latitude))
                        )
                    )
                )
            ";

            $products->selectRaw(
                "{$distanceSql} AS distance_meters",
                [
                    $customerLatitude,
                    $customerLongitude,
                    $customerLatitude,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Execute Products Query
        |--------------------------------------------------------------------------
        */

        $products = $products
            ->latest('ri.updated_at')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Transform Products
        |--------------------------------------------------------------------------
        */

        $products->transform(function ($product) {

            /*
            |--------------------------------------------------------------------------
            | Variant Label
            |--------------------------------------------------------------------------
            */

            $variant = '';

            if (
                $product->size_value &&
                $product->quantity_unit
            ) {

                $variant =
                    rtrim(
                        rtrim(
                            $product->size_value,
                            '0'
                        ),
                        '.'
                    )
                    .
                    $product->quantity_unit;

            } elseif (
                $product->weight_value &&
                $product->weight_unit
            ) {

                $variant =
                    rtrim(
                        rtrim(
                            $product->weight_value,
                            '0'
                        ),
                        '.'
                    )
                    .
                    $product->weight_unit;
            }

            if ($product->pieces) {

                $variant .=
                    ' (' .
                    $product->pieces .
                    'pcs)';
            }

            $product->variant_label = $variant;


            /*
            |--------------------------------------------------------------------------
            | Image URL
            |--------------------------------------------------------------------------
            */

            $baseMediaUrl = rtrim(
                env('MEDIA_URL'),
                '/'
            );

            $product->image_url =
                !empty($product->image_path)

                    ? $baseMediaUrl .
                        '/media/' .
                        ltrim(
                            $product->image_path,
                            '/'
                        )

                    : $baseMediaUrl .
                        '/media/img/homeMarket/products/item_image.png';


            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            */

            $now = Carbon::now();

            $hasActiveDiscount = false;

            $finalPrice =
                (float) $product->retail_price;

            $discountPercentage = 0;

            if (
                !is_null($product->discount) &&
                (float) $product->discount > 0
            ) {

                $startValid =
                    is_null($product->discount_start)
                    ||
                    $now->greaterThanOrEqualTo(
                        Carbon::parse(
                            $product->discount_start
                        )
                    );

                $stopValid =
                    is_null($product->discount_stop)
                    ||
                    $now->lessThanOrEqualTo(
                        Carbon::parse(
                            $product->discount_stop
                        )
                    );

                if (
                    $startValid &&
                    $stopValid
                ) {

                    $hasActiveDiscount = true;

                    $originalPrice =
                        (float) $product->retail_price;

                    $finalPrice =
                        max(
                            0,
                            $originalPrice -
                            (float) $product->discount
                        );

                    if ($originalPrice > 0) {

                        $discountPercentage =
                            round(
                                (
                                    (float) $product->discount /
                                    $originalPrice
                                ) * 100
                            );
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Discount Properties
            |--------------------------------------------------------------------------
            */

            $product->has_discount =
                $hasActiveDiscount;

            $product->final_price =
                $finalPrice;

            $product->discount_percentage =
                $discountPercentage;


            /*
            |--------------------------------------------------------------------------
            | Distance
            |--------------------------------------------------------------------------
            */

            if (
                isset($product->distance_meters) &&
                !is_null($product->distance_meters)
            ) {

                $product->distance_km =
                    round(
                        (float) $product->distance_meters / 1000,
                        2
                    );

            } else {

                $product->distance_km = null;
            }


            return $product;
        });


        /*
        |--------------------------------------------------------------------------
        | Return HomeMarket
        |--------------------------------------------------------------------------
        */

        return view(
            'HomeMarket.home',
            compact(
                'products',
                'selectedBusinessAccount'
            )
        );
    }


    /**
     * View a single inventory item.
     */
    public function ViewItem($inventoryId)
    {
        /*
        |--------------------------------------------------------------------------
        | Current Customer
        |--------------------------------------------------------------------------
        */

        $userId = auth('customer')->id();
        $sessionId = request()->session()->getId();


        /*
        |--------------------------------------------------------------------------
        | Determine Selected Cart Shop
        |--------------------------------------------------------------------------
        */

        $cartQuery = Cart::active();

        if ($userId) {

            $cartQuery->where(
                'user_id',
                $userId
            );

        } else {

            $cartQuery->where(
                'session_id',
                $sessionId
            );
        }

        $selectedBusinessAccount = $cartQuery
            ->whereNotNull('business_account')
            ->value('business_account');


        /*
        |--------------------------------------------------------------------------
        | Fetch Inventory Item
        |--------------------------------------------------------------------------
        */

        $productQuery = DB::connection('homemarket')
            ->table('retail_inventory as ri')

            ->join(
                'business as b',
                'b.account',
                '=',
                'ri.business_account'
            )

            ->join(
                'business_subscription as bs',
                function ($join) {

                    $join->on(
                        'bs.main_branch_account',
                        '=',
                        'b.main_branch_account'
                    )
                    ->where(
                        'bs.status',
                        'active'
                    );
                }
            )

            ->join(
                'tariffs as t',
                't.id',
                '=',
                'bs.tariff_id'
            )

            ->join(
                'products as p',
                'p.id',
                '=',
                'ri.product_id'
            )

            ->join(
                'product_items as pi',
                'pi.id',
                '=',
                'ri.item_id'
            )

            ->leftJoin(
                'product_variables.product_packaging as pv_pack',
                'pv_pack.id',
                '=',
                'pi.packaging_id'
            )

            ->leftJoin(
                'product_variables.quantity_units as qu',
                'qu.id',
                '=',
                'pi.quantity_unit_id'
            )

            ->leftJoin(
                'product_variables.weight_units as wu',
                'wu.id',
                '=',
                'pi.weight_unit_id'
            )

            ->where(
                'ri.id',
                $inventoryId
            )

            ->where(
                'bs.status',
                'active'
            )

            ->where(
                't.ecommerce_access',
                1
            )

            ->where(
                'b.shop_status',
                'open'
            )

            ->where(
                'ri.status',
                'instock'
            )

            ->whereNotNull(
                'ri.retail_price'
            );


        /*
        |--------------------------------------------------------------------------
        | Cart Shop Lock On Item View
        |--------------------------------------------------------------------------
        |
        | If the customer already has a shop selected, they cannot
        | directly open an item belonging to another shop.
        |
        */

        if ($selectedBusinessAccount) {

            $productQuery->where(
                'ri.business_account',
                $selectedBusinessAccount
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Select Item
        |--------------------------------------------------------------------------
        */

        $product = $productQuery
            ->select([

                'ri.*',

                'b.name as business_name',
                'b.account as business_account',

                'p.name as product_name',
                'p.description',

                'pi.size_value',
                'pi.weight_value',
                'pi.pieces',
                'pi.id as item_id',
                'pi.packaging_id',

                'qu.slug as quantity_unit',
                'wu.name as weight_unit',

                'pv_pack.name as packaging_name',
            ])
            ->first();


        /*
        |--------------------------------------------------------------------------
        | Item Not Found / Item Belongs To Another Shop
        |--------------------------------------------------------------------------
        */

        if (!$product) {

            abort(
                404,
                'The targeted inventory variation could not be found or is not available from your selected shop.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Variant Label
        |--------------------------------------------------------------------------
        */

        $variant = '';

        if (
            $product->size_value &&
            $product->quantity_unit
        ) {

            $variant =
                rtrim(
                    rtrim(
                        $product->size_value,
                        '0'
                    ),
                    '.'
                )
                .
                $product->quantity_unit;

        } elseif (
            $product->weight_value &&
            $product->weight_unit
        ) {

            $variant =
                rtrim(
                    rtrim(
                        $product->weight_value,
                        '0'
                    ),
                    '.'
                )
                .
                $product->weight_unit;
        }

        if ($product->pieces) {

            $variant .=
                ' (' .
                $product->pieces .
                'pcs)';
        }

        $product->variant_label = $variant;


        /*
        |--------------------------------------------------------------------------
        | Media URL
        |--------------------------------------------------------------------------
        */

        $rawUrl =
            config('app.media_url')
            ?:
            env('MEDIA_URL');

        $baseMediaUrl =
            rtrim(
                $rawUrl,
                '/'
            );


        /*
        |--------------------------------------------------------------------------
        | Primary Image
        |--------------------------------------------------------------------------
        */

        $imageRecord = DB::connection('homemarket')
            ->table('product_item_images')
            ->where(
                'product_item_id',
                $product->item_id
            )
            ->orderBy(
                'is_primary',
                'DESC'
            )
            ->first();

        $product->image_url =
            (
                $imageRecord &&
                !empty($imageRecord->image_path)
            )

                ? $baseMediaUrl .
                    '/media/' .
                    ltrim(
                        $imageRecord->image_path,
                        '/'
                    )

                : $baseMediaUrl .
                    '/media/img/homeMarket/products/item_image.png';


        /*
        |--------------------------------------------------------------------------
        | Carbon
        |--------------------------------------------------------------------------
        */

        $now = Carbon::now();


        /*
        |--------------------------------------------------------------------------
        | Alternate Product Variations
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | These remain locked to the same business.
        |
        */

        $product->gallery_images =
            DB::connection('homemarket')
                ->table('retail_inventory as ri')

                ->join(
                    'business as b',
                    'b.account',
                    '=',
                    'ri.business_account'
                )

                ->join(
                    'business_subscription as bs',
                    function ($join) {

                        $join->on(
                            'bs.main_branch_account',
                            '=',
                            'b.main_branch_account'
                        )
                        ->where(
                            'bs.status',
                            'active'
                        );
                    }
                )

                ->join(
                    'tariffs as t',
                    't.id',
                    '=',
                    'bs.tariff_id'
                )

                ->join(
                    'product_items as pi',
                    'pi.id',
                    '=',
                    'ri.item_id'
                )

                ->leftJoin(
                    'product_variables.quantity_units as qu',
                    'qu.id',
                    '=',
                    'pi.quantity_unit_id'
                )

                ->leftJoin(
                    'product_variables.weight_units as wu',
                    'wu.id',
                    '=',
                    'pi.weight_unit_id'
                )

                ->leftJoin(
                    'product_item_images as pii',
                    'pii.product_item_id',
                    '=',
                    'pi.id'
                )

                ->where(
                    'ri.product_id',
                    $product->product_id
                )

                ->where(
                    'pi.packaging_id',
                    $product->packaging_id
                )

                /*
                |--------------------------------------------------------------------------
                | Same Business Only
                |--------------------------------------------------------------------------
                */

                ->where(
                    'ri.business_account',
                    $product->business_account
                )

                ->where(
                    'bs.status',
                    'active'
                )

                ->where(
                    't.ecommerce_access',
                    1
                )

                ->where(
                    'b.shop_status',
                    'open'
                )

                ->where(
                    'ri.status',
                    'instock'
                )

                ->whereNotNull(
                    'ri.retail_price'
                )

                ->select([

                    'ri.id as inventory_id',

                    'ri.item_id',
                    'ri.retail_price',
                    'ri.discount',
                    'ri.discount_start',
                    'ri.discount_stop',

                    'pi.size_value',
                    'pi.weight_value',
                    'pi.pieces',

                    'qu.slug as quantity_unit',
                    'wu.name as weight_unit',

                    'pii.image_path',
                ])

                ->orderBy(
                    'pii.is_primary',
                    'DESC'
                )

                ->get()

                ->unique('item_id')

                ->map(
                    function ($variantItem) use (
                        $baseMediaUrl,
                        $now
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | Variant Label
                        |--------------------------------------------------------------------------
                        */

                        $label = '';

                        if (
                            $variantItem->size_value &&
                            $variantItem->quantity_unit
                        ) {

                            $label =
                                rtrim(
                                    rtrim(
                                        $variantItem->size_value,
                                        '0'
                                    ),
                                    '.'
                                )
                                .
                                $variantItem->quantity_unit;

                        } elseif (
                            $variantItem->weight_value &&
                            $variantItem->weight_unit
                        ) {

                            $label =
                                rtrim(
                                    rtrim(
                                        $variantItem->weight_value,
                                        '0'
                                    ),
                                    '.'
                                )
                                .
                                $variantItem->weight_unit;
                        }

                        if ($variantItem->pieces) {

                            $label .=
                                ' (' .
                                $variantItem->pieces .
                                'pcs)';
                        }

                        $variantItem->size_label =
                            $label;


                        /*
                        |--------------------------------------------------------------------------
                        | Image
                        |--------------------------------------------------------------------------
                        */

                        $variantItem->full_url =
                            !empty(
                                $variantItem->image_path
                            )

                                ? $baseMediaUrl .
                                    '/media/' .
                                    ltrim(
                                        $variantItem->image_path,
                                        '/'
                                    )

                                : $baseMediaUrl .
                                    '/media/img/homeMarket/products/item_image.png';


                        /*
                        |--------------------------------------------------------------------------
                        | Discount
                        |--------------------------------------------------------------------------
                        */

                        $hasActiveDiscount = false;

                        $originalPrice =
                            (float)
                            $variantItem->retail_price;

                        $finalPrice =
                            $originalPrice;

                        $discountPercentage = 0;

                        if (
                            !is_null(
                                $variantItem->discount
                            )
                            &&
                            (float)
                            $variantItem->discount > 0
                        ) {

                            $startValid =
                                is_null(
                                    $variantItem->discount_start
                                )
                                ||
                                $now->greaterThanOrEqualTo(
                                    Carbon::parse(
                                        $variantItem->discount_start
                                    )
                                );

                            $stopValid =
                                is_null(
                                    $variantItem->discount_stop
                                )
                                ||
                                $now->lessThanOrEqualTo(
                                    Carbon::parse(
                                        $variantItem->discount_stop
                                    )
                                );

                            if (
                                $startValid &&
                                $stopValid
                            ) {

                                $hasActiveDiscount =
                                    true;

                                $finalPrice =
                                    max(
                                        0,
                                        $originalPrice -
                                        (float)
                                        $variantItem->discount
                                    );

                                if (
                                    $originalPrice > 0
                                ) {

                                    $discountPercentage =
                                        round(
                                            (
                                                $variantItem->discount /
                                                $originalPrice
                                            ) * 100
                                        );
                                }
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Discount Properties
                        |--------------------------------------------------------------------------
                        */

                        $variantItem->has_discount =
                            $hasActiveDiscount;

                        $variantItem->original_price_formatted =
                            number_format(
                                $originalPrice
                            );

                        $variantItem->final_price_formatted =
                            number_format(
                                $finalPrice
                            );

                        $variantItem->discount_percentage =
                            $discountPercentage;


                        return $variantItem;
                    }
                );


        /*
        |--------------------------------------------------------------------------
        | Main Item Discount
        |--------------------------------------------------------------------------
        */

        $hasActiveDiscount = false;

        $finalPrice =
            (float) $product->retail_price;

        $discountPercentage = 0;

        if (
            !is_null($product->discount) &&
            (float) $product->discount > 0
        ) {

            $startValid =
                is_null($product->discount_start)
                ||
                $now->greaterThanOrEqualTo(
                    Carbon::parse(
                        $product->discount_start
                    )
                );

            $stopValid =
                is_null($product->discount_stop)
                ||
                $now->lessThanOrEqualTo(
                    Carbon::parse(
                        $product->discount_stop
                    )
                );

            if (
                $startValid &&
                $stopValid
            ) {

                $hasActiveDiscount = true;

                $originalPrice =
                    (float)
                    $product->retail_price;

                $finalPrice =
                    max(
                        0,
                        $originalPrice -
                        (float)
                        $product->discount
                    );

                if (
                    $originalPrice > 0
                ) {

                    $discountPercentage =
                        round(
                            (
                                (float)
                                $product->discount /
                                $originalPrice
                            ) * 100
                        );
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Main Discount Properties
        |--------------------------------------------------------------------------
        */

        $product->has_discount =
            $hasActiveDiscount;

        $product->final_price =
            $finalPrice;

        $product->discount_percentage =
            $discountPercentage;


        /*
        |--------------------------------------------------------------------------
        | Return Item View
        |--------------------------------------------------------------------------
        */

        return view(
            'HomeMarket.products.viewItem',
            compact('product')
        );
    }
}