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

                // shop & office
                'property.officeUnits',
                'property.officeImages', 
                'property.shopUnits',
                'property.shopImages',

                // Containers
                'property.containers',
                'property.containerImages',

                // Warehouse
                'property.warehouseUnits',
                'property.warehouseUnits.images',
                'property.warehouseType',

                // Hostels
                'property.hostelUnits',
                'property.hostelImages',
            ])
            ->get();

        $cards = $listings->flatMap(function ($listing) {

            $property = $listing->property;
            if (!$property) return [];

            /*
            |----------------------------------------------------------
            | ICON MAP
            |----------------------------------------------------------
            */
            $icon = fn($type) => match ($type) {
                'house'      => 'fa-solid fa-house-chimney',
                'sale'       => 'fa-solid fa-house-circle-check',
                'land'       => 'fa-solid fa-expand',
                'office'     => 'fa-regular fa-building',
                'shop'       => 'fa-solid fa-store',
                'container'  => 'fa-solid fa-box',
                'warehouse'  => 'fa-solid fa-warehouse',
                'hostel'     => 'fa-solid fa-bed',
                default      => 'fa-solid fa-building',
            };

            $groupKey = fn($type, $id) => $property->id . '-' . $type . '-' . $id;

            /*
            |----------------------------------------------------------
            | HOUSE RENT
            |----------------------------------------------------------
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
            |----------------------------------------------------------
            | HOUSE SALE
            |----------------------------------------------------------
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
            |----------------------------------------------------------
            | LAND
            |----------------------------------------------------------
            */
            $land = collect($property->landUnits)->map(function ($unit) use ($icon) {

                $measure = strtolower(optional($unit->measureUnit)->name ?? '');
                $size = trim($unit->size);
                $label = trim("{$size} {$measure}");
                $slug = str_replace(' ', '-', strtolower($label));

                return (object)[
                    'house_unit_type_id' => 'land-' . $slug,

                    // IMPORTANT: add this for image matching
                    'land_measure_unit_id' => $unit->land_measure_unit_id,

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
            |----------------------------------------------------------
            | OFFICE UNITS
            |----------------------------------------------------------
            */
            $offices = collect($property->officeUnits)->map(function ($unit) use ($icon, $property) {
                $measure = $unit->measure_unit ?? 'sqft';

                // Get the first available image from the property's office images
                $image = $property->officeImages->first()?->image_path ?? 'img/placeholder-office.jpg';

                // ONLY override size when by_size
                $displayValue = ($unit->rental_mode === 'by_size')
                    ? ($unit->remaining_space ?? $unit->size)
                    : $unit->size;

                $label = !empty($displayValue)
                    ? "Office {$displayValue} {$measure}"
                    : 'Office Units';

                return (object)[
                    'house_unit_type_id' => 'office',
                    'icon' => $icon('office'),
                    'type_name' => 'Office',
                    'unit_label' => $label,
                    'price' => $unit->price,
                    'image' => $image, // Path from office_images table

                    'price_suffix' => ($unit->rental_mode === 'by_size')
                        ? " per {$measure}"
                        : '',

                    'status' => $unit->status,
                    'type' => null,
                ];
            });


            /*
            |----------------------------------------------------------
            | SHOP UNITS
            |----------------------------------------------------------
            */
            $shops = collect($property->shopUnits)->map(function ($unit) use ($icon, $property) {
                $measure = $unit->measure_unit ?? 'sqft';

                // Get the first shop image from the property's relationship
                // optional() and the null-safe operator (?) prevent crashes if the relation is missing
                $image = optional($property->shopImages)->first()?->image_path ?? 'img/placeholder-shop.jpg';

                // ONLY override size when by_size
                $displayValue = ($unit->rental_mode === 'by_size')
                    ? ($unit->remaining_space ?? $unit->size)
                    : $unit->size;

                $label = !empty($displayValue)
                    ? "Shop {$displayValue} {$measure}"
                    : 'Shop Units';

                return (object)[
                    'house_unit_type_id' => 'shop',
                    'icon' => $icon('shop'),
                    'type_name' => 'Shop',
                    'unit_label' => $label,
                    'price' => $unit->price,
                    'image' => $image, // Path from shop_images table

                    'price_suffix' => ($unit->rental_mode === 'by_size')
                        ? " per {$measure}"
                        : '',

                    'status' => $unit->status,
                    'type' => null,
                ];
            });


            /*
            |----------------------------------------------------------
            | HOSTELS
            |----------------------------------------------------------
            */
            $hostel = collect($property->hostelUnits)->map(function ($unit) use ($groupKey, $icon) {

                return (object)[
                    'key' => $groupKey('hostel', $unit->id),
                    'house_unit_type_id' => 'hostel-' . $unit->total_beds . '-' . strtolower($unit->duration),
                    'icon' => $icon('hostel'),
                    'type_name' => 'Hostel Room',
                    'unit_label' => "{$unit->total_beds} Beds({$unit->duration})",
                    'price' => $unit->price,
                    'price_suffix' => ' per ' . strtolower($unit->duration),
                    'status' => $unit->status,
                    'type' => null,
                ];
            });

            /*
            |----------------------------------------------------------
            | CONTAINERS
            |----------------------------------------------------------
            */
            $containers = collect($property->containers)->map(function ($unit) use ($icon, $property) {

                // ✅ Proper filtering using DB column (NOT path)
                $image = optional(
                    $property->containerImages
                        ->where('container_size', $unit->size)
                        ->first()
                )->image_path ?? 'img/placeholder-container.jpg';

                return (object)[
                    'house_unit_type_id' => 'container-' . $unit->size,
                    'icon' => $icon('container'),

                    'type_name' => $unit->size . ' Container',
                    'unit_label' => "{$unit->size} Container",

                    'price' => $unit->price,
                    'price_suffix' => '',

                    'status' => $unit->units_available > 0 ? 'Available' : 'Sold Out',

                    'image' => $image, // ✅ clean & reliable

                    'type' => null,
                ];
            });
            /*
            |----------------------------------------------------------
            | WAREHOUSE
            |----------------------------------------------------------
            */
            $warehouseImage = $property->warehouseUnits
                ->first()
                ?->images
                ?->first()
                ?->image_path;

            $warehouse = collect($property->warehouseUnits)->map(function ($unit) use ($icon, $warehouseImage) {

                $measure = $unit->measure_unit ?? 'sqft';

                $displayValue = match ($unit->mode) {
                    'space' => $unit->total_space ?? 0,
                    'unit'  => $unit->unit_count ?? 0,
                    default => $unit->total_space ?? $unit->unit_count ?? 0,
                };

                $label = match ($unit->mode) {
                    'space' => "Warehouse ({$displayValue} {$measure})",
                    default => "Warehouse",
                };

                $suffix = match ($unit->mode) {
                    'space' => " per {$measure}",
                    default => '',
                };

                return (object)[
                    // ✅ KEY FIX: group everything into ONE card
                    'house_unit_type_id' => 'warehouse',

                    'icon' => $icon('warehouse'),
                    'type_name' => 'Warehouse',
                    'unit_label' => $label,
                    'price' => $unit->price,
                    'price_suffix' => $suffix,
                    'status' => $unit->status,

                    // 👇 shared image for all warehouse units
                    'image' => $warehouseImage,
                ];
            });
            /*
            |----------------------------------------------------------
            | MERGE ALL
            |----------------------------------------------------------
            */
            $units = collect()
                ->concat($house)
                ->concat($sale)
                ->concat($land)
                ->concat($offices)   // ✅ new
                ->concat($shops)     // ✅ new
                ->concat($hostel)
                ->concat($containers)
                ->concat($warehouse);

            if ($units->isEmpty()) return [];

            /*
            |----------------------------------------------------------
            | GROUP & FORMAT CARDS
            |----------------------------------------------------------
            */
            return $units->groupBy('house_unit_type_id')
                ->map(function ($group) use ($property, $listing) {
                    $unit = $group->first();
                    $id = $unit->house_unit_type_id;

                    /*

                    |----------------------------------------------------------
                    | DYNAMIC IMAGE SELECTION
                    |----------------------------------------------------------
                    */
                    if ($id === 'office') {
                        $rawPath = $property->officeImages->first()?->image_path;

                    } elseif ($id === 'shop') {
                        $rawPath = $property->shopImages->first()?->image_path;

                    }elseif (str_starts_with($id, 'sale-')) {

                        $typeId = str_replace('sale-', '', $id);

                        $saleImages = $property->houseOnSaleUnitTypeImages ?? collect();

                        $rawPath = optional(
                            $saleImages->where('house_unit_type_id', $typeId)->first()
                        )->image_path
                        ?? $property->featured_image
                        ?? 'img/default-property.jpg';
                    }elseif (str_starts_with($id, 'land-')) {

                        $measureId = $unit->land_measure_unit_id ?? null;

                        $landImages = $property->landImages ?? collect();

                        $rawPath = optional(
                            $landImages
                                ->where('land_measure_unit_id', $measureId)
                                ->first()
                        )->image_path
                        ?? optional($landImages->first())->image_path
                        ?? $property->featured_image
                        ?? 'img/default-property.jpg';
                    }elseif (str_starts_with($id, 'hostel-')) {

                        $rawPath = $property->hostelImages->first()?->image_path
                            ?? $property->featured_image
                            ?? 'img/default-property.jpg';

                    }elseif ($id === 'warehouse') {
                        $rawPath = $property->warehouseUnits
                            ->first()
                            ?->images
                            ?->first()
                            ?->image_path
                            ?? $property->featured_image
                            ?? 'img/default-property.jpg';

                    }elseif (str_starts_with($id, 'container-')) {
                        $rawPath = $unit->image ?? null;
                    

                    }else {
                        $rawPath =
                            $unit->type?->images?->first()?->image_path
                            ?? $property->featured_image
                            ?? 'img/default-property.jpg';
                    }

                    $finalPath = $rawPath ? ltrim($rawPath, '/') : null;
                    $displayImage = $finalPath
                        ? rtrim(env('MEDIA_URL'), '/') . '/' . $finalPath
                        : asset('img/default-property.jpg');

                    $min = $group->min('price');
                    $max = $group->max('price');

                    return (object)[
                        'key'           => $listing->id . '-' . $id,
                        'listing_id'    => $listing->id,
                        'property_id'   => $property->id,
                        'display_title' => $property->title,
                        'icon'          => $unit->icon,
                        'unit_type'     => $unit->unit_label ?? $unit->type_name,
                        'display_image' => $displayImage,
                        'min_price'     => $min,
                        'max_price'     => $max,
                        'price_suffix'  => $unit->price_suffix ?? '',
                        'price_display' => $min == $max
                            ? number_format($min) . ($unit->price_suffix ?? '')
                            : number_format($min) . ' - ' . number_format($max) . ($unit->price_suffix ?? ''),
                        'location'      => $property->formatted_location,
                        'type_label'    => match ($property->listing_type) {
                            'onSale'  => 'For Sale',
                            'forRent' => 'For Rent',
                            default   => 'Listing',
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

        // Residential
        'property.houseUnits.type.images',
        'property.houseOnSaleUnits.type.images',

        // Land
        'property.landImages',

        // Commercial
        'property.officeImages',
        'property.shopImages',

        // Units
        'property.houseUnits',
        'property.houseOnSaleUnits',
        'property.houseOnSaleTypeImages',

        'property.landUnits.measureUnit',
        'property.hostelUnits',
        'property.containers',
        'property.warehouseUnits',

        // Warehouse images (IMPORTANT)
        'property.warehouseUnits.images',

        'property.containerImages',

        'lister'
    ])->findOrFail($id);

    $property = $listing->property;

    $baseUrl = rtrim(env('MEDIA_URL'), '/');

    /*
    |------------------------------------------------------------
    | GALLERY BUILDER (FIXED & UNIFIED)
    |------------------------------------------------------------
    */
    $allImages = collect();

    // HOUSE (RENT)
    foreach ($property->houseUnits ?? [] as $unit) {
        foreach ($unit->type?->images ?? [] as $img) {
            $allImages->push([
                'path'  => $img->image_path,
                'label' => $img->label,
            ]);
        }
    }

    // HOUSE (SALE)
    foreach ($property->houseOnSaleUnits ?? [] as $unit) {
        $images = $property->houseOnSaleTypeImages
            ->where('house_unit_type_id', $unit->house_unit_type_id);

        foreach ($images as $img) {
            $allImages->push([
                'path'  => $img->image_path,
                'label' => $img->label,
            ]);
        }
    }

    // LAND (PROPERTY LEVEL)
    foreach ($property->landImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // HOSTEL (PROPERTY LEVEL)
    foreach ($property->hostelImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // OFFICE
    foreach ($property->officeImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // SHOP
    foreach ($property->shopImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // CONTAINER
    foreach ($property->containerImages ?? [] as $img) {
        $allImages->push([
            'path'  => $img->image_path,
            'label' => $img->label,
        ]);
    }

    // WAREHOUSE (UNIT LEVEL)
    foreach ($property->warehouseUnits ?? [] as $unit) {
        foreach ($unit->images ?? [] as $img) {
            $allImages->push([
                'path'  => $img->image_path,
                'label' => $img->label,
            ]);
        }
    }

    /*
    |------------------------------------------------------------
    | FORMAT IMAGES (SAFE URL BUILD)
    |------------------------------------------------------------
    */
    $allImages = $allImages
        ->filter(fn ($img) => !empty($img['path']))
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
    | UNIT MAPPING (UNCHANGED LOGIC, CLEANED NULL SAFETY)
    |------------------------------------------------------------
    */
    $mapUnits = function ($items, $type) use ($property) {

        return collect($items ?? [])->map(function ($u) use ($type, $property) {

            $price = $u->price ?? $u->unit_price ?? 0;

            $status = strtolower($u->status ?? 'vacant');

            $css = match ($status) {
                'vacant', 'available' => 'vacant',
                'booked' => 'booked',
                default => 'occupied'
            };

            $name = match ($type) {
                'house', 'sale' => $u->unit_number ?? 'Unit',
                'hostel' => $u->room_number ?? 'Room',
                'container' => $u->size ?? 'Container',

                'warehouse' => $u->mode === 'space'
                    ? ($u->total_space . ' ' . ($u->measure_unit ?? 'sqft'))
                    : ($u->unit_count . ' Units'),

                'office', 'shop' => !empty($u->size)
                    ? $u->size . ' ' . ($u->measure_unit ?? 'sqft')
                    : ($u->unit_number ?? 'Unit'),

                'land' => $u->size ?? 'Land',
                default => 'Unit',
            };

            $displayType = match ($type) {
                'house', 'sale' => optional($u->type)->name ?? 'House',
                'land' => optional($u->measureUnit)->name ?? 'Land',
                'warehouse' => 'Warehouse',
                'office' => 'Office',
                'shop' => 'Shop',
                'hostel' => 'Hostel Room',
                'container' => 'Container',
                default => ucfirst($type),
            };

            $suffix = match ($type) {
                'hostel' => ' per ' . strtolower($u->duration ?? 'month'),
                'warehouse' => $u->mode === 'space'
                    ? ' per ' . strtolower($u->measure_unit ?? 'sqft')
                    : '',
                default => ''
            };

            return (object)[
                'id' => $u->id,
                'display_name' => $name,
                'display_type' => $displayType,
                'display_price' => number_format($price),
                'price' => $price,
                'status' => $status,
                'css_class' => $css,
                'price_suffix' => $suffix,
                'type' => $type,
                'display_size' => $u->size ?? null,
                'display_remaining' => $u->remaining_space ?? null,
            ];
        });
    };

    /*
    |------------------------------------------------------------
    | ALL UNITS
    |------------------------------------------------------------
    */
    $property->all_units = collect()
        ->concat($mapUnits($property->houseUnits, 'house'))
        ->concat($mapUnits($property->houseOnSaleUnits, 'sale'))
        ->concat($mapUnits($property->landUnits, 'land'))
        ->concat($mapUnits($property->hostelUnits, 'hostel'))
        ->concat($mapUnits($property->containers, 'container'))
        ->concat($mapUnits($property->warehouseUnits, 'warehouse'))
        ->concat($mapUnits($property->officeUnits, 'office'))
        ->concat($mapUnits($property->shopUnits, 'shop'));

    /*
    |------------------------------------------------------------
    | PRICE RANGE
    |------------------------------------------------------------
    */
    $property->min_price = $property->all_units->min('price') ?? 0;
    $property->max_price = $property->all_units->max('price') ?? 0;

    $property->display_price = ($property->min_price > 0)
        ? (
            $property->min_price == $property->max_price
                ? 'Ksh ' . number_format($property->min_price)
                : 'Ksh ' . number_format($property->min_price) . ' - ' . number_format($property->max_price)
        )
        : 'Price on Request';

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

    return view('HomeCity.Properties.viewProperty', compact('listing', 'property'));
}

}
