<?php

namespace App\Http\Controllers\Web\HomeCity;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use App\Models\HomeCity\Listing;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Display all properties on the homepage
     */
    
    public function index()
    {
        $listings = Listing::on('homecity')
            ->where('status', 'active')
            ->with([
                'property.location',

                // Residential
                'property.houseUnits.type.images',
                'property.houseOnSaleUnits.type.images',

                // Land
                'property.landUnits.measureUnit',

                // Commercial
                'property.commercialSpaces',

                // Containers
                'property.containers',

                // Warehouse
                'property.warehouseUnits',
                'property.warehouseType',

                // Hostels
                'property.hostelUnits',
            ])
            ->get();

        $cards = $listings->flatMap(function ($listing) {

            $property = $listing->property;
            if (!$property) return [];

            /*
            |--------------------------------------------------------------------------
            | ICON MAP
            |--------------------------------------------------------------------------
            */
            $icon = fn($type) => match ($type) {
                'house'      => 'fa-solid fa-house-chimney',
                'sale'       => 'fa-solid fa-house-circle-check',
                'land'       => 'fa-solid fa-expand',
                'commercial' => 'fa-solid fa-store',
                'container'  => 'fa-solid fa-box',
                'warehouse'  => 'fa-solid fa-warehouse',
                'hostel'     => 'fa-solid fa-bed',
                default      => 'fa-solid fa-building',
            };

            $groupKey = fn($type, $id) => $property->id . '-' . $type . '-' . $id;

            /*
            |--------------------------------------------------------------------------
            | HOUSE RENT
            |--------------------------------------------------------------------------
            */
            $house = collect($property->houseUnits)->map(fn($unit) => (object)[
                'key' => $groupKey('house', $unit->house_unit_type_id),
                'house_unit_type_id' => 'house-' . $unit->house_unit_type_id,
                'icon' => $icon('house'),
                'type_name' => optional($unit->type)->name ?? 'Unit',
                'unit_label' => optional($unit->type)->name ?? 'Unit',
                'price' => $unit->price,
                'price_suffix' => '',
                'status' => $unit->status,
                'type' => $unit->type,
            ]);

            /*
            |--------------------------------------------------------------------------
            | HOUSE SALE
            |--------------------------------------------------------------------------
            */
            $sale = collect($property->houseOnSaleUnits)->map(fn($unit) => (object)[
                'key' => $groupKey('sale', $unit->house_unit_type_id),
                'house_unit_type_id' => 'sale-' . $unit->house_unit_type_id,
                'icon' => $icon('sale'),
                'type_name' => optional($unit->type)->name ?? 'Unit',
                'unit_label' => optional($unit->type)->name ?? 'Unit',
                'price' => $unit->price,
                'price_suffix' => '',
                'status' => $unit->status,
                'type' => $unit->type,
            ]);

            /*
            |--------------------------------------------------------------------------
            | LAND
            |--------------------------------------------------------------------------
            */
            $land = collect($property->landUnits)->map(function ($unit) use ($icon) {

                $measure = strtolower(optional($unit->measureUnit)->name ?? '');
                $size = preg_replace('/\s*x\s*/i', ' x ', trim($unit->size));
                $label = trim("{$size} {$measure}");
                $slug = str_replace(' ', '-', strtolower($label));

                return (object)[
                    'house_unit_type_id' => 'land-' . $slug,
                    'icon' => $icon('land'),
                    'type_name' => $measure ?: 'Land',
                    'unit_label' => $label,
                    'price' => $unit->price,
                    'price_suffix' => '',
                    'status' => $unit->status,
                    'type' => null,
                ];
            });

            /*
            |--------------------------------------------------------------------------
            | COMMERCIAL
            |--------------------------------------------------------------------------
            */
            $commercial = collect($property->commercialSpaces)->map(function ($space) use ($icon) {

                $type = strtolower($space->space_type ?? 'space');
                $measure = $space->measure_unit ?? 'sqft';

                if ($space->rental_mode === 'by_size') {
                    $label = ucfirst($type) . " ({$space->total_space} {$measure})";
                    $slug = "{$type}-{$space->total_space}-{$measure}";
                    $price = $space->price_per_sqr;
                    $suffix = " per sq {$measure}";
                } else {
                    $label = ucfirst($type);
                    $slug = "{$type}-unit";
                    $price = $space->unit_price;
                    $suffix = '';
                }

                return (object)[
                    'house_unit_type_id' => 'comm-' . strtolower($slug),
                    'icon' => $type === 'office' ? 'fa-regular fa-building' : $icon('commercial'),
                    'unit_label' => $label,
                    'price' => $price,
                    'price_suffix' => $suffix,
                    'status' => 'active',
                    'type' => null,
                ];
            });

            /*
            |--------------------------------------------------------------------------
            | HOSTELS
            |--------------------------------------------------------------------------
            */
            $hostel = collect($property->hostelUnits)->map(function ($unit) use ($groupKey, $icon) {

                return (object)[
                    'key' => $groupKey('hostel', $unit->id),
                    'house_unit_type_id' => 'hostel-' . $unit->total_beds . '-' . strtolower($unit->duration),
                    'icon' => $icon('hostel'),
                    'type_name' => 'Hostel Room',
                    'unit_label' => "{$unit->total_beds} Bed Room ({$unit->duration})",
                    'price' => $unit->price,
                    'price_suffix' => ' per ' . strtolower($unit->duration),
                    'status' => match ($unit->status) {
                        'vacant' => 'Available',
                        'booked' => 'Full',
                        'occupied' => 'Occupied',
                        default => 'Available',
                    },
                    'type' => null,
                ];
            });

            /*
            |--------------------------------------------------------------------------
            | CONTAINERS
            |--------------------------------------------------------------------------
            */
            $containers = collect($property->containers)->map(fn($unit) => (object)[
                'key' => $groupKey('container', $unit->id),
                'house_unit_type_id' => 'container-' . $unit->id,
                'icon' => $icon('container'),
                'type_name' => $unit->size . ' Container',
                'unit_label' => $unit->size,
                'price' => $unit->price,
                'price_suffix' => '',
                'status' => $unit->units_available > 0 ? 'Available' : 'Sold Out',
                'type' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | WAREHOUSE
            |--------------------------------------------------------------------------
            */
            $warehouseType = optional($property->warehouseType)->name ?? 'Warehouse';

            $warehouse = collect($property->warehouseUnits)->map(fn($unit) => (object)[
                'key' => $groupKey('warehouse', $property->warehouse_property_type_id),
                'house_unit_type_id' => 'warehouse-' . $property->warehouse_property_type_id,
                'icon' => $icon('warehouse'),
                'type_name' => $warehouseType,
                'unit_label' => $warehouseType,
                'price' => $unit->price,
                'price_suffix' => '',
                'status' => $unit->status,
                'type' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | MERGE
            |--------------------------------------------------------------------------
            */
            $units = collect()
                ->concat($house)
                ->concat($sale)
                ->concat($land)
                ->concat($commercial)
                ->concat($hostel)
                ->concat($containers)
                ->concat($warehouse);

            if ($units->isEmpty()) return [];

            /*
            |--------------------------------------------------------------------------
            | FINAL GROUPING
            |--------------------------------------------------------------------------
            */
            return $units->groupBy('house_unit_type_id')
                ->map(function ($group) use ($property, $listing) {

                    $unit = $group->first();

                    // Inside the final map function
                    $rawPath = ($unit->type && $unit->type->images->isNotEmpty()) 
                        ? $unit->type->images->first()->image_path 
                        : $property->featured_image;

                    // This prevents double slashes (e.g., media//image.jpg)
                    $finalPath = ltrim($rawPath, '/');

                    $displayImage = $rawPath 
                        ? env('MEDIA_URL') . '/' . $finalPath 
                        : asset('img/default-property.jpg');


                    $min = $group->min('price');
                    $max = $group->max('price');

                    return (object)[
                        'key' => $listing->id . '-' . $unit->house_unit_type_id,
                        'listing_id'      => $listing->id, 
                        'property_id' => $property->id,
                        'display_title' => $property->title,
                        'icon' => $unit->icon,
                        'unit_type' => $unit->unit_label ?? $unit->type_name,
                        'display_image'   => $displayImage,

                        'min_price' => $min,
                        'max_price' => $max,
                        'price_suffix' => $unit->price_suffix ?? '',

                        // ✅ ready for UI
                        'price_display' => $min == $max
                            ? number_format($min) . ($unit->price_suffix ?? '')
                            : number_format($min) . ' - ' . number_format($max) . ($unit->price_suffix ?? ''),

                        'location' => $property->formatted_location,

                        'type_label' => match ($property->listing_type) {
                            'onSale' => 'For Sale',
                            'onLease' => 'For Lease',
                            'forRent' => 'For Rent',
                            default => 'For Sale',
                        },
                    ];
                })
                ->values();
        })
        ->unique('key')
        ->values();

        return view('HomeCity.home', [
            'listings' => $cards
        ]);
    }



    /**
     * Display a single property with all related info
     */
    public function viewListing($id)
    {
        $listing = Listing::on('homecity')->with([
            'property.location.county',
            'property.location.town',
            'property.location.place',

            // Units + images
            'property.houseUnits.type.images',
            'property.houseOnSaleUnits.type.images',
            'property.landUnits.measureUnit',

            // Commercial
            'property.commercialSpaces',

            'property.hostelUnits',
            'property.containers',
            'property.warehouseUnits',
            'property.warehouseType',

            'property.amenities',
            'lister'
        ])->findOrFail($id);

        $property = $listing->property;

        $baseUrl = rtrim(env('MEDIA_URL'), '/');

        /*
        |------------------------------------------------------------
        | SPLIT COMMERCIAL SPACES (OFFICE vs SHOP)
        |------------------------------------------------------------
        */
        $commercialSpaces = collect($property->commercialSpaces ?? []);

        $offices = $commercialSpaces->where('space_type', 'office')->values();
        $shops   = $commercialSpaces->where('space_type', 'shop')->values();

        /*
        |------------------------------------------------------------
        | IMAGE COLLECTION
        |------------------------------------------------------------
        */
        $allImages = collect();

        $pushImages = function ($items) use (&$allImages) {
            foreach ($items ?? [] as $item) {
                if (!empty($item->type?->images)) {
                    $allImages = $allImages->merge(
                        $item->type->images->map(fn($img) => [
                            'path'  => $img->image_path,
                            'label' => $img->label ?? $img->name ?? null,
                        ])
                    );
                }
            }
        };

        $pushImages($property->houseUnits);
        $pushImages($property->houseOnSaleUnits);

        foreach ($commercialSpaces as $space) {
            if (!empty($space->image_path)) {
                $allImages->push([
                    'path'  => $space->image_path,
                    'label' => ucfirst($space->space_type ?? 'space'),
                ]);
            }
        }

        $allImages = $allImages
            ->filter(fn($img) => !empty($img['path']))
            ->map(function ($img) use ($baseUrl) {

                $path = ltrim($img['path'], '/');

                return [
                    'url' => str_starts_with($path, 'http')
                        ? $path
                        : $baseUrl . '/' . $path,
                    'label' => $img['label'] ?? null,
                ];
            })
            ->unique('url')
            ->values();

        $property->display_image = $allImages->first()['url']
            ?? asset('img/default-property.jpg');

        $property->gallery_list = $allImages;

        /*
        |------------------------------------------------------------
        | UNIT MAPPING
        |------------------------------------------------------------
        */
        $mapUnits = function ($items, $type) use ($property) {

            return collect($items ?? [])->map(function ($u) use ($type, $property) {

                $price = $u->price
                    ?? $u->unit_price
                    ?? $u->price_per_sqr
                    ?? 0;

                $status = strtolower($u->status ?? 'vacant');

                $css = match ($status) {
                    'vacant', 'available', 'active' => 'vacant',
                    'booked' => 'booked',
                    default => 'occupied'
                };

                /*
                |----------------------------------------------------
                | LABELS
                |----------------------------------------------------
                */
                $name = match ($type) {
                    'house'     => $u->unit_number ?? 'House Unit',
                    'sale'      => $u->unit_number ?? 'House Unit',
                    'hostel'    => $u->room_number ?? 'Hostel Room',
                    'container' => 'Container',
                    'warehouse' => $u->warehouse_code ?? 'unit',
                    'office', 'shop' => !empty($u->total_space)
                    ? $u->total_space . ' ' . ($u->measure_unit ?? 'sqft')
                    : ucfirst($type),
                    'land'      => $u->size ?? 'Land',
                    default     => 'Unit',
                };

                /*
                |----------------------------------------------------
                | RENTAL MODE LOGIC (IMPORTANT FIX)
                |----------------------------------------------------
                */
                $suffix = match ($type) {

                    'hostel' => 'per ' . strtolower($u->duration ?? 'month'),

                    
                    
                    // 'warehouse' => 'per sq ft',

                    'office', 'shop' => match ($u->rental_mode ?? null) {
                        'by_size' => 'per ' . strtolower($u->measure_unit ?? 'ft'),
                        'by_unit' => 'per unit',
                        default => ''
                    },

                    'warehouse' => match ($u->mode ?? null) {
                        'space' => 'per ' . strtolower($u->measure_unit ?? 'ft'),
                        'unit' => 'per unit',
                        default => ''
                    },

                    default => ''
                };

                /*
                |----------------------------------------------------
                | REMAINING SPACE (ONLY FOR by_size COMMERCIAL)
                |----------------------------------------------------
                */
                $remaining = null;

                if (in_array($type, ['office', 'shop']) && $u->rental_mode === 'by_size') {
                    $remaining = max(0, ($u->total_space ?? 0) - ($u->occupied_space ?? 0));
                }

                return (object)[
                    'id' => $u->id,

                    // UNIT IDENTIFIER
                    'display_name' => $name,

                    // ACTUAL BUSINESS TYPE
                    'display_type' => match ($type) {

                        // RENTAL HOUSES
                        'house' => optional($u->type)->name ?? 'House Unit',

                        // HOUSES FOR SALE
                        'sale' => optional($u->type)->name ?? 'House Unit',

                        // LAND
                        'land' => optional($u->measureUnit)->name ?? 'Land',

                        // WAREHOUSE
                        'warehouse' => optional($property->warehouseType)->name ?? 'Warehouse',

                        // COMMERCIAL
                        'office' => 'Office Space',
                        'shop' => 'Shop Space',

                        // HOSTELS
                        'hostel' => 'Hostel Room',

                        // CONTAINERS
                        'container' => ($u->size ?? ''),

                        default => ucfirst($type),
                    },

                    // PRICE
                    'price' => $price,
                    'display_price' => number_format($price),

                    // STATUS
                    'status' => $status,
                    'css_class' => $css,

                    // RENT MODE LABEL
                    'price_suffix' => $suffix,

                    // RAW TYPE
                    'type' => $type,

                    // RENTAL MODE
                    'rental_mode' => $u->rental_mode ?? null,

                    // SIZE INFO
                    // 'display_size' => $size,

                    // AVAILABLE SPACE
                    'display_remaining' => $remaining,
                ];
            });
        };

        /*
        |------------------------------------------------------------
        | ALL UNITS (WITH SPLIT COMMERCIAL)
        |------------------------------------------------------------
        */
        $property->all_units = collect()
            ->concat($mapUnits($property->houseUnits, 'house'))
            ->concat($mapUnits($property->houseOnSaleUnits, 'sale'))
            ->concat($mapUnits($property->landUnits, 'land'))
            ->concat($mapUnits($property->hostelUnits, 'hostel'))
            ->concat($mapUnits($property->containers, 'container'))
            ->concat($mapUnits($property->warehouseUnits, 'warehouse'))
            ->concat($mapUnits($offices, 'office'))
            ->concat($mapUnits($shops, 'shop'));

        /*
        |------------------------------------------------------------
        | PRICE RANGE
        |------------------------------------------------------------
        */
        $property->min_price = $property->all_units->min('price') ?? 0;
        $property->max_price = $property->all_units->max('price') ?? 0;

        $suffixes = $property->all_units->pluck('price_suffix')->filter()->unique();

        $property->price_suffix = $suffixes->count() === 1
            ? $suffixes->first()
            : null;

        $property->display_price = ($property->min_price > 0)
            ? (
                $property->min_price == $property->max_price
                    ? 'Ksh ' . number_format($property->min_price)
                    : 'Ksh ' . number_format($property->min_price) . ' - ' . number_format($property->max_price)
            )
            : 'Price on Request';

        /*
        |------------------------------------------------------------
        | EXPOSURE TO BLADE (IMPORTANT FOR FILTERING UI)
        |------------------------------------------------------------
        */
        $property->offices = $offices;
        $property->shops   = $shops;

        /*
        |------------------------------------------------------------
        | LABELS
        |------------------------------------------------------------
        */
        $property->type_label = match ($property->listing_type) {
            'onSale'  => 'For Sale',
            'onLease' => 'For Lease',
            'forRent' => 'For Rent',
            default   => 'Listing',
        };

        $property->location_label = $property->formatted_location;

        return view('HomeCity.viewProperty', compact('listing', 'property'));
    }

}
