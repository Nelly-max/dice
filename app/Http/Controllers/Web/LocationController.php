<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    /** Get all countries */
    public function getCountries()
    {
        return DB::table('countries')
            ->select('id', 'name')
            ->get();
    }

    /** Get counties for a given country */
    public function getCounties($countryId)
    {
        return DB::table('physical_counties')
            ->where('country_id', $countryId)
            ->select('id', 'name')
            ->get();
    }

    /** Get towns for a given county */
    public function getTowns($countyId)
    {
        return DB::table('physical_towns')
            ->where('physical_county_id', $countyId)
            ->select('id', 'name')
            ->get();
    }

    /** Get places for a given town */
    public function getPlaces($townId)
    {
        return DB::table('physical_places')
            ->where('physical_town_id', $townId) // matches your migration FK
            ->select('id', 'name')
            ->get();
    }
}
