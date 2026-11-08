<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MajorDivision; 
use App\Models\Subdivision; 

class SubdivisionsController extends Controller
{
    public function index()
    {
        // Only get major divisions that have at least one active subdivision
        $majorDivisions = MajorDivision::whereHas('subDivisions', function($query) {
            $query->where('status', 'active'); // only active subdivisions
        })->with(['subDivisions' => function($query) {
            $query->where('status', 'active'); // only active subdivisions
        }])->get();

        return view('home', compact('majorDivisions'));
    }

    /**
     * "Products": every active subdivision on one page (no grouping).
     */
    public function products()
    {
        $majorDivisions = MajorDivision::whereHas('subDivisions', function ($query) {
            $query->where('status', 'active');
        })->with(['subDivisions' => function ($query) {
            $query->where('status', 'active')->orderBy('name');
        }])->get();

        $subdivisions = $majorDivisions
            ->flatMap(fn ($major) => $major->subDivisions->map(function ($sub) use ($major) {
                $sub->division_name = $major->name;
                return $sub;
            }))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('products', compact('subdivisions', 'majorDivisions'));
    }

    public function handleWeblink($weblink)
    {
        // Find the subdivision by its weblink attribute
        $subdivision = Subdivision::where('weblink', $weblink)
            ->where('status', 'active')
            ->firstOrFail();

        // Subdivisions with their own pages, whatever their weblink is called in the hub
        $name = strtolower($subdivision->name . ' ' . $weblink);
        $pages = [
            'liquor' => 'liquorhub.home',   // hub weblink may be "liquorhub", "liquor_hub", ...
            'glam'   => 'glamroom.home',
            'farm'   => 'farmmarket.home',
            'beauty' => 'glamroom.home',
            'airbnb' => 'airbnb.index',
            'water'  => 'water.index',
        ];
        foreach ($pages as $key => $route) {
            if (str_contains($name, $key) && \Illuminate\Support\Facades\Route::has($route)) {
                return redirect()->route($route);
            }
        }

        // Anything else: the "coming soon" page
        return view('subdivision.details', compact('subdivision'));
    }
}
