<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MajorDivision; 
use App\Models\SubDivision; 

class SubdivisionsController extends Controller
{
    public function index()
    {
        // Only get major divisions that have at least one active subdivision
        $majorDivisions = MajorDivision::whereHas('subDivisions', function($query) {
            $query->where('status', 1); // only active subdivisions
        })->with(['subDivisions' => function($query) {
            $query->where('status', 1); // only active subdivisions
        }])->get();

        return view('home', compact('majorDivisions'));
    }

    public function handleWeblink($weblink)
    {
        // Find the subdivision by its weblink attribute
        $subdivision = SubDivision::where('weblink', $weblink)
            ->where('status', 1)
            ->firstOrFail();

        // Return the specific view for the subdivision
        return view('subdivision.details', compact('subdivision'));
    }
}
