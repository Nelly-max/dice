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

        // Return the specific view for the subdivision
        return view('subdivision.details', compact('subdivision'));
    }
}
