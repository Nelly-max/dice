<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryLocationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ]);

        $request->session()->put('delivery_location', [
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'location' => [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
            ],
        ]);
    }

    public function get(Request $request)
    {
        $location = $request->session()->get('delivery_location');

        return response()->json([
            'success' => true,
            'location' => $location,
        ]);
    }

    public function clear(Request $request)
    {
        $request->session()->forget('delivery_location');

        return response()->json([
            'success' => true,
        ]);
    }
}