<?php

namespace App\Http\Controllers\Web\HomeMarket;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Customer\Cart;

use App\Services\DeliveryLocationService;

class ItemsController extends Controller
{
    /**
     * Display all HomeMarket items.
     */
    public function index(
        Request $request,
        DeliveryLocationService $locationService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Current Customer
        |--------------------------------------------------------------------------
        */

        $userId = auth('customer')->id();
        $sessionId = $request->session()->getId();


        /*
        |--------------------------------------------------------------------------
        | Current Cart Shop
        |--------------------------------------------------------------------------
        */

        $selectedBusinessAccount = $this->getSelectedBusinessAccount(
            $userId,
            $sessionId
        );


        /*
        |--------------------------------------------------------------------------
        | Customer Delivery Location
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Coordinates are NOT read from the URL.
        |
        | They come from the Laravel session through
        | DeliveryLocationService.
        |
        */

        $deliveryLocation = $locationService->get($request);

        $customerLatitude = $deliveryLocation['latitude'] ?? null;
        $customerLongitude = $deliveryLocation['longitude'] ?? null;

        $hasDeliveryLocation =
            !is_null($customerLatitude) &&
            !is_null($customerLongitude);


        /*
        |--------------------------------------------------------------------------
        | Determine Progressive Radius
        |--------------------------------------------------------------------------
        |
        | If the customer has a delivery location and does not already
        | have a cart shop, find the nearest radius containing at least
        | one qualifying shop.
        |
        | 500m
        | 1km
        | 2km
        | 3km maximum
        |
        */

        $selectedRadius = null;

        if (
            $hasDeliveryLocation &&
            !$selectedBusinessAccount
        ) {
            $selectedRadius = $this->resolveDeliveryRadius(
                $customerLatitude,
                $customerLongitude
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Products Query
        |--------------------------------------------------------------------------
        */

        $products = DB::connection('homemarket')
            ->table('retail_inventory as ri')

            /*
            |------------------------------------------------------------------
            | Business
            |------------------------------------------------------------------
            */

            ->join(
                'business as b',
                'b.account',
                '=',
                'ri.business_account'
            )

            /*
            |------------------------------------------------------------------
            | Active Subscription
            |------------------------------------------------------------------
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
            |------------------------------------------------------------------
            | Tariff
            |------------------------------------------------------------------
            */

            ->join(
                'tariffs as t',
                't.id',
                '=',
                'bs.tariff_id'
            )

            /*
            |------------------------------------------------------------------
            | Product
            |------------------------------------------------------------------
            */

            ->join(
                'products as p',
                'p.id',
                '=',
                'ri.product_id'
            )

            /*
            |------------------------------------------------------------------
            | Product Item
            |------------------------------------------------------------------
            */

            ->join(
                'product_items as pi',
                'pi.id',
                '=',
                'ri.item_id'
            )

            /*
            |------------------------------------------------------------------
            | Packaging
            |------------------------------------------------------------------
            */

            ->leftJoin(
                'product_variables.product_packaging as pv_pack',
                'pv_pack.id',
                '=',
                'pi.packaging_id'
            )

            /*
            |------------------------------------------------------------------
            | Quantity Unit
            |------------------------------------------------------------------
            */

            ->leftJoin(
                'product_variables.quantity_units as qu',
                'qu.id',
                '=',
                'pi.quantity_unit_id'
            )

            /*
            |------------------------------------------------------------------
            | Weight Unit
            |------------------------------------------------------------------
            */

            ->leftJoin(
                'product_variables.weight_units as wu',
                'wu.id',
                '=',
                'pi.weight_unit_id'
            )

            /*
            |------------------------------------------------------------------
            | Basic Product Eligibility
            |------------------------------------------------------------------
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
        | 1. Existing cart shop
        | 2. Delivery location
        | 3. All qualifying shops
        |
        */

        if ($selectedBusinessAccount) {

            /*
            |------------------------------------------------------------------
            | Existing Cart Shop
            |------------------------------------------------------------------
            */

            $products->where(
                'ri.business_account',
                $selectedBusinessAccount
            );

        } elseif ($hasDeliveryLocation) {

            /*
            |------------------------------------------------------------------
            | Delivery Location
            |------------------------------------------------------------------
            */

            if (is_null($selectedRadius)) {

                /*
                | No qualifying shop exists within the maximum 3km.
                |
                | Therefore show no products.
                */

                $products->whereRaw(
                    '1 = 0'
                );

            } else {

                $this->applyDistanceFilter(
                    $products,
                    $customerLatitude,
                    $customerLongitude,
                    $selectedRadius
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Select Product Attributes
        |--------------------------------------------------------------------------
        */

        $products->select([

            /*
            |------------------------------------------------------------------
            | Retail Inventory
            |------------------------------------------------------------------
            */

            'ri.*',

            /*
            |------------------------------------------------------------------
            | Business
            |------------------------------------------------------------------
            */

            'b.name as business_name',
            'b.account as business_account',
            'b.latitude as business_latitude',
            'b.longitude as business_longitude',

            /*
            |------------------------------------------------------------------
            | Product
            |------------------------------------------------------------------
            */

            'p.id as product_id',
            'p.code as product_code',
            'p.name as product_name',
            'p.brand',
            'p.description',

            /*
            |------------------------------------------------------------------
            | Product Item
            |------------------------------------------------------------------
            */

            'pi.id as item_id',
            'pi.code as item_code',
            'pi.size_value',
            'pi.weight_value',
            'pi.pieces',

            /*
            |------------------------------------------------------------------
            | Units
            |------------------------------------------------------------------
            */

            'qu.slug as quantity_unit',
            'wu.name as weight_unit',

            /*
            |------------------------------------------------------------------
            | Packaging
            |------------------------------------------------------------------
            */

            'pv_pack.name as packaging_name',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Primary Product Image
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
        | Distance
        |--------------------------------------------------------------------------
        |
        | Only calculate distance when the customer is discovering shops
        | using their delivery location.
        |
        */

        if (
            $hasDeliveryLocation &&
            !$selectedBusinessAccount &&
            !is_null($selectedRadius)
        ) {
            $this->addDistanceSelect(
                $products,
                $customerLatitude,
                $customerLongitude
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Execute Query
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
            |------------------------------------------------------------------
            | Variant Label
            |------------------------------------------------------------------
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
                    . $product->quantity_unit;

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
                    . $product->weight_unit;
            }

            if ($product->pieces) {
                $variant .=
                    ' (' .
                    $product->pieces .
                    'pcs)';
            }

            $product->variant_label = $variant;


            /*
            |------------------------------------------------------------------
            | Image URL
            |------------------------------------------------------------------
            */


            $baseMediaUrl = rtrim(
                config('app.media_url'),
                '/'
            );

            $product->image_url =
                !empty($product->image_path)
                    ? $baseMediaUrl . '/' .
                        ltrim($product->image_path, '/')
                    : $baseMediaUrl .
                        '/img/homeMarket/products/item_image.png';


            /*
            |------------------------------------------------------------------
            | Discount
            |------------------------------------------------------------------
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
            |------------------------------------------------------------------
            | Discount Properties
            |------------------------------------------------------------------
            */

            $product->has_discount =
                $hasActiveDiscount;

            $product->final_price =
                $finalPrice;

            $product->discount_percentage =
                $discountPercentage;


            /*
            |------------------------------------------------------------------
            | Distance
            |------------------------------------------------------------------
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
     *
     * The same shop/location rules used by the homepage are respected.
     */
    public function ViewItem(
        $inventoryId,
        Request $request,
        DeliveryLocationService $locationService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Current Customer
        |--------------------------------------------------------------------------
        */

        $userId = auth('customer')->id();
        $sessionId = $request->session()->getId();


        /*
        |--------------------------------------------------------------------------
        | Selected Cart Shop
        |--------------------------------------------------------------------------
        */

        $selectedBusinessAccount =
            $this->getSelectedBusinessAccount(
                $userId,
                $sessionId
            );


        /*
        |--------------------------------------------------------------------------
        | Delivery Location
        |--------------------------------------------------------------------------
        */

        $deliveryLocation =
            $locationService->get($request);

        $customerLatitude =
            $deliveryLocation['latitude'] ?? null;

        $customerLongitude =
            $deliveryLocation['longitude'] ?? null;

        $hasDeliveryLocation =
            !is_null($customerLatitude) &&
            !is_null($customerLongitude);


        /*
        |--------------------------------------------------------------------------
        | Progressive Radius
        |--------------------------------------------------------------------------
        |
        | Only used when there is no cart shop.
        |
        */

        $selectedRadius = null;

        if (
            $hasDeliveryLocation &&
            !$selectedBusinessAccount
        ) {
            $selectedRadius =
                $this->resolveDeliveryRadius(
                    $customerLatitude,
                    $customerLongitude
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Product Query
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
                    );

                    $join->where(
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
        | Item Shop / Location Rules
        |--------------------------------------------------------------------------
        */

        if ($selectedBusinessAccount) {

            /*
            |------------------------------------------------------------------
            | Existing Cart Shop Has Priority
            |------------------------------------------------------------------
            */

            $productQuery->where(
                'ri.business_account',
                $selectedBusinessAccount
            );

        } elseif ($hasDeliveryLocation) {

            /*
            |------------------------------------------------------------------
            | Customer Location
            |------------------------------------------------------------------
            */

            if (is_null($selectedRadius)) {

                /*
                | No qualifying shop within 3km.
                */

                $productQuery->whereRaw(
                    '1 = 0'
                );

            } else {

                $this->applyDistanceFilter(
                    $productQuery,
                    $customerLatitude,
                    $customerLongitude,
                    $selectedRadius
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Select Main Product
        |--------------------------------------------------------------------------
        */

        $product = $productQuery
            ->select([

                'ri.*',

                'b.name as business_name',
                'b.account as business_account',
                'b.latitude as business_latitude',
                'b.longitude as business_longitude',

                'p.id as product_id',
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
        | Item Not Available
        |--------------------------------------------------------------------------
        */

        if (!$product) {
            abort(
                404,
                'The targeted inventory variation could not be found or is not available from your selected shop or delivery area.'
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
                . $product->quantity_unit;

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
                . $product->weight_unit;
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

        $imageRecord =
            DB::connection('homemarket')
                ->table('product_item_images')
                ->where(
                    'product_item_id',
                    $product->item_id
                )
                ->orderBy(
                    'is_primary',
                    'DESC'
                )
                ->orderBy(
                    'id',
                    'ASC'
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
        | Current Time
        |--------------------------------------------------------------------------
        */

        $now = Carbon::now();


        /*
        |--------------------------------------------------------------------------
        | Alternate Product Variations
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Variations remain locked to the same business as the
        | currently viewed item.
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
                        );

                        $join->where(
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
                |------------------------------------------------------------------
                | Same Business
                |------------------------------------------------------------------
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

                ->orderBy(
                    'pii.id',
                    'ASC'
                )

                ->get()

                ->unique('item_id')

                ->map(
                    function ($variantItem) use (
                        $baseMediaUrl,
                        $now
                    ) {

                        /*
                        |------------------------------------------------------
                        | Variant Label
                        |------------------------------------------------------
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
                        |------------------------------------------------------
                        | Image
                        |------------------------------------------------------
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
                        |------------------------------------------------------
                        | Discount
                        |------------------------------------------------------
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
                                $hasActiveDiscount = true;

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
                        |------------------------------------------------------
                        | Discount Properties
                        |------------------------------------------------------
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


    /*
    |--------------------------------------------------------------------------
    | PRIVATE HELPERS
    |--------------------------------------------------------------------------
    */


    /**
     * Get the business account currently represented in the active cart.
     */
    private function getSelectedBusinessAccount(
        $userId,
        string $sessionId
    ): ?string {
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

        return $cartQuery
            ->whereNotNull('business_account')
            ->value('business_account');
    }


/**
 * Resolve the customer's progressive delivery radius.
 *
 * Radius progression:
 *
 * 1 km
 * 3 km
 * 5 km maximum
 *
 * The first radius containing at least one
 * qualifying shop is selected.
 *
 * Returns:
 * 1000, 3000, 5000
 *
 * or null when no qualifying shop exists
 * within 5km.
 */
private function resolveDeliveryRadius(
    float $customerLatitude,
    float $customerLongitude
): ?int {
    /*
    |--------------------------------------------------------------------------
    | Progressive delivery radius
    |--------------------------------------------------------------------------
    |
    | 1 km -> 2 km -> 3 km maximum
    |
    | The first radius containing at least one qualifying shop wins.
    |
    */

    $radiusOptions = [
        1000, // 1 km
        2000, // 2 km
        3000, // 3 km maximum
    ];

    foreach ($radiusOptions as $radius) {

        $distanceSql = $this->distanceSql();

        $hasShop = DB::connection('homemarket')
            ->table('business as b')

            /*
            |--------------------------------------------------------------------------
            | Active subscription
            |--------------------------------------------------------------------------
            */
            ->join('business_subscription as bs', function ($join) {
                $join->on(
                    'bs.main_branch_account',
                    '=',
                    'b.main_branch_account'
                )->where(
                    'bs.status',
                    'active'
                );
            })

            /*
            |--------------------------------------------------------------------------
            | Ecommerce tariff
            |--------------------------------------------------------------------------
            */
            ->join(
                'tariffs as t',
                't.id',
                '=',
                'bs.tariff_id'
            )

            ->where('t.ecommerce_access', 1)

            /*
            |--------------------------------------------------------------------------
            | Shop must be open
            |--------------------------------------------------------------------------
            */
            ->where('b.shop_status', 'open')

            /*
            |--------------------------------------------------------------------------
            | Shop must have valid coordinates
            |--------------------------------------------------------------------------
            */
            ->whereNotNull('b.latitude')
            ->whereNotNull('b.longitude')

            /*
            |--------------------------------------------------------------------------
            | Shop must have qualifying inventory
            |--------------------------------------------------------------------------
            */
            ->whereExists(function ($query) {

                $query->select(DB::raw(1))
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
            | Distance from customer
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

        if ($hasShop) {
            return $radius;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | No qualifying shop within 3 km
    |--------------------------------------------------------------------------
    */

    return null;
}


    /**
     * Apply a delivery-radius filter to a product query.
     */
    private function applyDistanceFilter(
        $query,
        float $customerLatitude,
        float $customerLongitude,
        int $radius
    ): void {
        $distanceSql =
            $this->distanceSql();

        $query
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
                    $radius,
                ]
            );
    }


    /**
     * Add calculated distance to a product query.
     */
    private function addDistanceSelect(
        $query,
        float $customerLatitude,
        float $customerLongitude
    ): void {
        $distanceSql =
            $this->distanceSql();

        $query->selectRaw(
            "{$distanceSql} AS distance_meters",
            [
                $customerLatitude,
                $customerLongitude,
                $customerLatitude,
            ]
        );
    }


    /**
     * Haversine distance SQL.
     *
     * Returns distance in meters.
     */
    private function distanceSql(): string
    {
        return "
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
    }
}