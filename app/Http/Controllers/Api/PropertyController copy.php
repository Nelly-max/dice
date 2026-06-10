<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    /**
     * Get all properties with unit-type images, location, category, type, units, amenities, and lister.
     */
    public function index()
    {
        $properties = Property::with([
            'unitTypeImages',
            'location',
            'category',
            'type',
            'units.type',
            'lister',
            'amenities',
        ])->get();

        $properties->transform(function ($property) {

            // ✅ Build full URLs for all unit-type images
            $property->unitTypeImages->transform(function ($image) {
                $image->url = asset($image->image_path);
                return $image;
            });

            // ✅ First image (fallback to placeholder)
            $property->first_image_url = $property->unitTypeImages->first()->url
                ?? 'https://via.placeholder.com/400x300?text=No+Image';

            // -----------------------------------------------------
            // UNIT / PRICE LOGIC
            // -----------------------------------------------------

            $vacantUnits = $property->units->where('status', 'Vacant');

            if ($vacantUnits->isEmpty()) {
                $property->price = 'Price on Request';
            } else {
                $min = (int) $vacantUnits->min('price');
                $max = (int) $vacantUnits->max('price');

                $property->price = ($min === $max)
                    ? 'Ksh ' . number_format($min)
                    : 'Ksh ' . number_format($min) . ' - Ksh ' . number_format($max);
            }

            // ✅ Vacant unit types
            $property->vacant_unit_types = $vacantUnits
                ->pluck('type.name')
                ->unique()
                ->values();

            // -----------------------------------------------------
            // LOCATION / LISTER / AMENITIES
            // -----------------------------------------------------

            $property->location_formatted =
                $property->location
                    ? trim(($property->location->county ?? '') . ', ' . ($property->location->town ?? ''), ', ')
                    : 'Location not available';

            if ($property->lister) {
                $property->lister = [
                    'id' => $property->lister->id,
                    'name' => $property->lister->name,
                    'phone' => $property->lister->phone ?? 'N/A',
                    'email' => $property->lister->email ?? 'N/A',
                    'photo' => $property->lister->photo
                        ? asset($property->lister->photo)
                        : asset('img/default-avatar.png'),
                ];
            }

            // Simple amenities list
            $property->amenities_list = $property->amenities->pluck('name');

            return $property;
        });

        return response()->json($properties);
    }


    /**
     * Get a single property with full details.
     */
    public function show($id)
    {
        $property = Property::with([
            'unitTypeImages',
            'location',
            'category',
            'type',
            'units.type',
            'amenities',
            'lister',
        ])->findOrFail($id);

        // 🔹 Fix image URLs (unit type images only)
        $property->unitTypeImages->transform(function ($image) {
            $image->url = asset($image->image_path);
            return $image;
        });

        $property->first_image_url = $property->unitTypeImages->first()->url
            ?? 'https://via.placeholder.com/400x300?text=No+Image';

        // -----------------------------------------------------
        // UNIT + PRICE LOGIC
        // -----------------------------------------------------

        $vacantUnits = $property->units->where('status', 'Vacant');

        if ($vacantUnits->isEmpty()) {
            $property->price = 'Price on Request';
        } else {
            $min = (int) $vacantUnits->min('price');
            $max = (int) $vacantUnits->max('price');

            $property->price = ($min === $max)
                ? 'Ksh ' . number_format($min)
                : 'Ksh ' . number_format($min) . ' - Ksh ' . number_format($max);
        }

        $property->vacant_unit_types = $vacantUnits
            ->pluck('type.name')
            ->unique()
            ->values();

        // Location
        $property->location_formatted =
            $property->location
                ? trim(($property->location->county ?? '') . ', ' . ($property->location->town ?? ''), ', ')
                : 'Location not available';

        // Amenities
        $property->amenities = $property->amenities->map(function ($amenity) {
            return [
                'id' => $amenity->id,
                'name' => $amenity->name,
                'icon' => $amenity->icon,
            ];
        });

        // Lister
        if ($property->lister) {
            $property->lister = [
                'id' => $property->lister->id,
                'name' => $property->lister->name,
                'phone' => $property->lister->phone ?? 'N/A',
                'email' => $property->lister->email ?? 'N/A',
                'photo' => $property->lister->photo
                    ? asset($property->lister->photo)
                    : asset('img/default-avatar.png'),
            ];
        }

        return response()->json($property);
    }
}
