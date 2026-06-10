<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    /**
     * Helper to format location names from IDs using the default (Hub) connection
     */
    private function getFormattedLocation($location)
    {
        if (!$location) return 'Location not available';

        // Fetch names from default 'hub' database using the IDs stored in homecity
        $county = DB::table('physical_counties')->where('id', $location->county_id)->value('name');
        $town   = DB::table('physical_towns')->where('id', $location->town_id)->value('name');

        return trim(($county ?? '') . ', ' . ($town ?? ''), ', ') ?: 'Location not available';
    }

    /**
     * Helper to handle the C: drive image URLs via the 'media' symlink
     */
    private function getImageUrl($path)
    {
        if (!$path) return null;
        // Prefix with 'media' because of our Administrator symlink: mklink /D public\media C:\media
        return asset('media/' . $path);
    }

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

            // ✅ Fix Image URLs (Using media symlink)
            $property->unitTypeImages->transform(function ($image) {
                $image->url = $this->getImageUrl($image->image_path);
                return $image;
            });

            $property->first_image_url = $property->unitTypeImages->first()->url
                ?? 'https://via.placeholder.com/400x300?text=No+Image';

            // ✅ Unit / Price Logic
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

            // ✅ Resolve Hub IDs to Names
            $property->location_formatted = $this->getFormattedLocation($property->location);

            // ✅ Lister Formatting
            if ($property->lister) {
                $property->lister_data = [
                    'id' => $property->lister->id,
                    'name' => $property->lister->name,
                    'phone' => $property->lister->phone ?? 'N/A',
                    'photo' => $property->lister->photo
                        ? asset($property->lister->photo)
                        : asset('img/default-avatar.png'),
                ];
            }

            $property->amenities_list = $property->amenities->pluck('name');

            return $property;
        });

        return response()->json($properties);
    }

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

        // ✅ Fix Image URLs
        $property->unitTypeImages->transform(function ($image) {
            $image->url = $this->getImageUrl($image->image_path);
            return $image;
        });

        $property->first_image_url = $property->unitTypeImages->first()->url
            ?? 'https://via.placeholder.com/400x300?text=No+Image';

        // ✅ Price Logic
        $vacantUnits = $property->units->where('status', 'Vacant');
        if ($vacantUnits->isEmpty()) {
            $property->price = 'Price on Request';
        } else {
            $min = (int) $vacantUnits->min('price');
            $max = (int) $vacantUnits->max('price');
            $property->price = ($min === $max) ? 'Ksh ' . number_format($min) : 'Ksh ' . number_format($min) . ' - Ksh ' . number_format($max);
        }

        // ✅ Resolve Hub IDs to Names
        $property->location_formatted = $this->getFormattedLocation($property->location);

        // ✅ Lister Photo
        if ($property->lister && $property->lister->photo) {
            $property->lister->photo_url = asset($property->lister->photo);
        }

        return response()->json($property);
    }
}
