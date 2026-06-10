<?php

namespace App\Http\Controllers\Web\HomeMarket;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\HomeCity\Listing;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ItemsController extends Controller
{
    /**
     * Display all items on the homepage
     */

    
    public function index()
    {
        $products = DB::connection('homemarket')
            ->table('retail_inventory as ri')

            /*

            |--------------------------------------------------------------------------
            | Business
            |--------------------------------------------------------------------------
            */
            ->join('business as b', 'b.account', '=', 'ri.business_account')

            /*

            |--------------------------------------------------------------------------
            | Active Subscription
            |--------------------------------------------------------------------------
            */
            ->join('business_subscription as bs', function ($join) {
                $join->on('bs.main_branch_account', '=', 'b.main_branch_account')
                     ->where('bs.status', '=', 'active');
            })

            /*

            |--------------------------------------------------------------------------
            | Tariff Access
            |--------------------------------------------------------------------------
            */
            ->join('tariffs as t', 't.id', '=', 'bs.tariff_id')

            /*

            |--------------------------------------------------------------------------
            | Products
            |--------------------------------------------------------------------------
            */
            ->join('products as p', 'p.id', '=', 'ri.product_id')

            /*

            |--------------------------------------------------------------------------
            | Product Items
            |--------------------------------------------------------------------------
            */
            ->join('product_items as pi', 'pi.id', '=', 'ri.item_id')

            /*

            |--------------------------------------------------------------------------
            | Product Packaging (Cross Database Lookup Join)
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
            | Quantity Units (Cross Database)
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
            | Weight Units (Cross Database)
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
            | Conditions
            |--------------------------------------------------------------------------
            */
            ->where('bs.status', 'active')
            ->where('t.ecommerce_access', 1)
            ->where('b.shop_status', 'open')
            ->where('ri.status', 'instock')
            ->whereNotNull('ri.retail_price')

            /*

            |--------------------------------------------------------------------------
            | Select Attributes Array
            |--------------------------------------------------------------------------
            */
            ->select([
                'ri.*',

                'b.name as business_name',
                'b.account as business_account',

                'p.id as product_id',
                'p.code as product_code',
                'p.name as product_name',
                'p.brand',
                'p.description',

                'pi.id as item_id',
                'pi.code as item_code',
                'pi.size_value',
                'pi.weight_value',
                'pi.pieces',

                'qu.slug as quantity_unit',
                'wu.name as weight_unit',
                
                // Targets the valid dedicated table metadata column name
                'pv_pack.name as packaging_name', 
            ])

            /*

            |--------------------------------------------------------------------------
            | Product Image (First Available Image)
            |--------------------------------------------------------------------------
            */
            ->selectRaw("
                (
                    SELECT pii.image_path
                    FROM product_item_images as pii
                    WHERE pii.product_item_id = pi.id
                    ORDER BY pii.is_primary DESC, pii.id ASC
                    LIMIT 1
                ) as image_path
            ")

            ->latest('ri.updated_at')
            ->get()

            ->map(function ($product) {

                /*

                |--------------------------------------------------------------------------
                | Variant Label Calculation
                |--------------------------------------------------------------------------
                */
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

                /*

                |--------------------------------------------------------------------------
                | Image URL Resolution
                |--------------------------------------------------------------------------
                */
                $baseMediaUrl = rtrim(env('MEDIA_URL'), '/');

                $product->image_url = !empty($product->image_path)
                    ? $baseMediaUrl . '/media/' . ltrim($product->image_path, '/')
                    : $baseMediaUrl . '/media/img/homeMarket/products/item_image.png';

                /*

                |--------------------------------------------------------------------------
                | Live Discount Schedule Evaluator
                |--------------------------------------------------------------------------
                */
                $now = Carbon::now();
                $hasActiveDiscount = false;
                $finalPrice = (float) $product->retail_price;
                $discountPercentage = 0;

                if (!is_null($product->discount) && (float) $product->discount > 0) {
                    
                    $startValid = is_null($product->discount_start) || $now->greaterThanOrEqualTo(Carbon::parse($product->discount_start));
                    $stopValid  = is_null($product->discount_stop)  || $now->lessThanOrEqualTo(Carbon::parse($product->discount_stop));

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

                return $product;
            });

        return view('HomeMarket.home', compact('products'));
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
