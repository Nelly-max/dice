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
     * Display all items on the homepage
     */

    

    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Get Current Customer Cart Shop
        |--------------------------------------------------------------------------
        */

        $userId = auth('customer')->id();
        $sessionId = $request->session()->getId();

        $cartQuery = Cart::active();

        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $selectedBusinessAccount = $cartQuery
            ->whereNotNull('business_account')
            ->value('business_account');


        /*
        |--------------------------------------------------------------------------
        | Customer Delivery Coordinates
        |--------------------------------------------------------------------------
        |
        | These are passed from the HomeMarket URL:
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
        | Search order:
        |
        | 500 metres
        | 1 kilometre
        | 2 kilometres
        | 3 kilometres
        |
        | The first radius containing at least one qualifying shop
        | becomes the selected radius.
        |
        */

        $selectedRadius = null;

        if ($hasDeliveryLocation) {

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
                |
                | Returns distance in metres.
                |
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
                |
                | A shop qualifies when:
                |
                | - It is open
                | - It has an active subscription
                | - Its tariff allows ecommerce
                | - It has at least one instock retail item
                | - That item has a retail price
                | - The shop has coordinates
                | - The shop is inside the current radius
                |
                */

                $hasShop = DB::connection('homemarket')
                    ->table('business as b')

                    /*
                    |--------------------------------------------------------------------------
                    | Active Subscription
                    |--------------------------------------------------------------------------
                    */

                    ->join('business_subscription as bs', function ($join) {

                        $join->on(
                            'bs.main_branch_account',
                            '=',
                            'b.main_branch_account'
                        );

                        $join->where(
                            'bs.status',
                            'active'
                        );
                    })

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
                    | Valid Coordinates
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

            ->join('business_subscription as bs', function ($join) {

                $join->on(
                    'bs.main_branch_account',
                    '=',
                    'b.main_branch_account'
                );

                $join->where(
                    'bs.status',
                    'active'
                );
            })

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
        | Delivery Radius Has Priority
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Even if the cart contains a shop such as RHM002A,
        | the delivery location determines which shops are available.
        |
        */

        if ($hasDeliveryLocation) {

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

        } elseif ($selectedBusinessAccount) {

            /*
            |--------------------------------------------------------------------------
            | No Delivery Location
            |
            | If there is no delivery location but the cart already has
            | a selected shop, keep the existing cart-shop behavior.
            |--------------------------------------------------------------------------
            */

            $products->where(
                'ri.business_account',
                $selectedBusinessAccount
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Select Product Attributes
        |--------------------------------------------------------------------------
        */

        $products->select([

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
        */

        if ($hasDeliveryLocation) {

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

            $product->has_discount =
                $hasActiveDiscount;

            $product->final_price =
                $finalPrice;

            $product->discount_percentage =
                $discountPercentage;


            /*
            |--------------------------------------------------------------------------
            | Distance In Kilometres
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




    // 🔥 Change the parameter to target the unique inventory table row index
    public function ViewItem($inventoryId)
    {
        // 1. Fetch the single active inventory row requested by the consumer
        $product = DB::connection('homemarket')
            ->table('retail_inventory as ri')
            ->join('business as b', 'b.account', '=', 'ri.business_account')
            ->join('business_subscription as bs', function ($join) {
                $join->on('bs.main_branch_account', '=', 'b.main_branch_account')
                    ->where('bs.status', '=', 'active');
            })
            ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
            ->join('products as p', 'p.id', '=', 'ri.product_id')
            ->join('product_items as pi', 'pi.id', '=', 'ri.item_id')
            ->leftJoin('product_variables.product_packaging as pv_pack', 'pv_pack.id', '=', 'pi.packaging_id')
            ->leftJoin('product_variables.quantity_units as qu', 'qu.id', '=', 'pi.quantity_unit_id')
            ->leftJoin('product_variables.weight_units as wu', 'wu.id', '=', 'pi.weight_unit_id')
            // 🔥 CRITICAL FIX: Match the unique row ID instead of the shared item layout definition index
            ->where('ri.id', $inventoryId)
            ->where('bs.status', 'active')
            ->where('t.ecommerce_access', 1)
            ->where('b.shop_status', 'open')
            ->where('ri.status', 'instock')
            ->whereNotNull('ri.retail_price')
            ->select([
                // ri.id as inventory_id is pulled along automatically via ri.*
                'ri.*', 
                'b.name as business_name', 'b.account as business_account',
                'p.name as product_name', 'p.description',
                'pi.size_value', 'pi.weight_value', 'pi.pieces', 'pi.id as item_id', 'pi.packaging_id',
                'qu.slug as quantity_unit', 'wu.name as weight_unit',
                'pv_pack.name as packaging_name'
            ])
            ->first();

        if (!$product) {
            abort(404, 'The targeted inventory variation could not be found.');
        }

        // 2. Resolve variant packaging text labeling strings for the main item
        $variant = '';
        if ($product->size_value && $product->quantity_unit) {
            $variant = rtrim(rtrim($product->size_value, '0'), '.') . $product->quantity_unit;
        } elseif ($product->weight_value && $product->weight_unit) {
            $variant = rtrim(rtrim($product->weight_value, '0'), '.') . $product->weight_unit;
        }
        if ($product->pieces) { 
            $variant .= ' (' . $product->pieces . 'pcs)'; 
        }
        $product->variant_label = $variant;

        // 3. Evaluate clean media root URL endpoints safely
        $rawUrl = config('app.media_url') ?: env('MEDIA_URL');
        $baseMediaUrl = rtrim($rawUrl, '/');

        // 4. Resolve the active main frame primary item photo path mapping URL
        $imageRecord = DB::connection('homemarket')
            ->table('product_item_images')
            ->where('product_item_id', $product->item_id)
            ->orderBy('is_primary', 'DESC')
            ->first();

        $product->image_url = ($imageRecord && !empty($imageRecord->image_path))
            ? $baseMediaUrl . '/media/' . ltrim($imageRecord->image_path, '/')
            : $baseMediaUrl . '/media/img/homeMarket/products/item_image.png';

        // 5. Setup shared carbon clock point instance for tracking running discount validations
        $now = \Carbon\Carbon::now();

        // 6. Query for alternate package variations (sibling sizes) sold strictly by this SAME business
        $product->gallery_images = DB::connection('homemarket')
            ->table('retail_inventory as ri')
            ->join('business as b', 'b.account', '=', 'ri.business_account')
            ->join('business_subscription as bs', function ($join) {
                $join->on('bs.main_branch_account', '=', 'b.main_branch_account')
                    ->where('bs.status', '=', 'active');
            })
            ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')
            ->join('product_items as pi', 'pi.id', '=', 'ri.item_id')
            ->leftJoin('product_variables.quantity_units as qu', 'qu.id', '=', 'pi.quantity_unit_id')
            ->leftJoin('product_variables.weight_units as wu', 'wu.id', '=', 'pi.weight_unit_id')
            ->leftJoin('product_item_images as pii', 'pii.product_item_id', '=', 'pi.id')
            ->where('ri.product_id', $product->product_id)
            ->where('pi.packaging_id', $product->packaging_id)
            // 🔥 SCOPE LOCK: Kept secure by matching the known parent row business account field
            ->where('ri.business_account', $product->business_account)
            ->where('bs.status', 'active')
            ->where('t.ecommerce_access', 1)
            ->where('b.shop_status', 'open')
            ->where('ri.status', 'instock')
            ->whereNotNull('ri.retail_price')
            ->select([
                // 🔥 CRITICAL: Select ri.id so child buttons can link to their own specific row entries
                'ri.id as inventory_id', 
                'ri.item_id', 'ri.retail_price', 'ri.discount', 'ri.discount_start', 'ri.discount_stop',
                'pi.size_value', 'pi.weight_value', 'pi.pieces',
                'qu.slug as quantity_unit', 'wu.name as weight_unit',
                'pii.image_path'
            ])
            ->orderBy('pii.is_primary', 'DESC')
            ->get()
            ->unique('item_id')
            ->map(function ($variantItem) use ($baseMediaUrl, $now) {
                $label = '';
                if ($variantItem->size_value && $variantItem->quantity_unit) {
                    $label = rtrim(rtrim($variantItem->size_value, '0'), '.') . $variantItem->quantity_unit;
                } elseif ($variantItem->weight_value && $variantItem->weight_unit) {
                    $label = rtrim(rtrim($variantItem->weight_value, '0'), '.') . $variantItem->weight_unit;
                }
                if ($variantItem->pieces) { 
                    $label .= ' (' . $variantItem->pieces . 'pcs)'; 
                }
                $variantItem->size_label = $label;
                
                $variantItem->full_url = !empty($variantItem->image_path)
                    ? $baseMediaUrl . '/media/' . ltrim($variantItem->image_path, '/')
                    : $baseMediaUrl . '/media/img/homeMarket/products/item_image.png';

                $hasActiveDiscount = false;
                $originalPrice = (float) $variantItem->retail_price;
                $finalPrice = $originalPrice;
                $discountPercentage = 0;

                if (!is_null($variantItem->discount) && (float) $variantItem->discount > 0) {
                    $startValid = is_null($variantItem->discount_start) || $now->greaterThanOrEqualTo(\Carbon\Carbon::parse($variantItem->discount_start));
                    $stopValid  = is_null($variantItem->discount_stop)  || $now->lessThanOrEqualTo(\Carbon\Carbon::parse($variantItem->discount_stop));

                    if ($startValid && $stopValid) {
                        $hasActiveDiscount = true;
                        $finalPrice = max(0, $originalPrice - (float) $variantItem->discount);
                        if ($originalPrice > 0) {
                            $discountPercentage = round(($variantItem->discount / $originalPrice) * 100);
                        }
                    }
                }

                $variantItem->has_discount = $hasActiveDiscount;
                $variantItem->original_price_formatted = number_format($originalPrice);
                $variantItem->final_price_formatted = number_format($finalPrice);
                $variantItem->discount_percentage = $discountPercentage;

                return $variantItem;
            });

        // 7. Markdown Discount Computational Engine Map
        $hasActiveDiscount = false;
        $finalPrice = (float) $product->retail_price;
        $discountPercentage = 0;

        if (!is_null($product->discount) && (float) $product->discount > 0) {
            $startValid = is_null($product->discount_start) || $now->greaterThanOrEqualTo(\Carbon\Carbon::parse($product->discount_start));
            $stopValid  = is_null($product->discount_stop)  || $now->lessThanOrEqualTo(\Carbon\Carbon::parse($product->discount_stop));

            if ($startValid && $stopValid) {
                $hasActiveDiscount = true;
                $originalPrice = (float) $product->retail_price;
                $finalPrice = max(0, $originalPrice - (float) $product->discount);
                if ($originalPrice > 0) {
                    $discountPercentage = round(($product->discount / $originalPrice) * 100);
                }
            }
        }

        $product->has_discount = $hasActiveDiscount;
        $product->final_price = $finalPrice;
        $product->discount_percentage = $discountPercentage;

        return view('HomeMarket.products.viewItem', compact('product'));
    }





}
