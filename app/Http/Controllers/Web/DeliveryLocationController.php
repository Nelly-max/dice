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
            // what the buyer sees for this pin (shown on checkout, which can't change it)
            'address' => ['nullable', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:120'],
            'town' => ['nullable', 'string', 'max:120'],
        ]);

        $request->session()->put('delivery_location', [
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'address' => $validated['address'] ?? null,
            'county' => $validated['county'] ?? null,
            'town' => $validated['town'] ?? null,
        ]);

        // a new pin means a new delivery cost
        $request->session()->forget('checkout.delivery');

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