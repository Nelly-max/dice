<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeliveryLocationService
{
    public function get(Request $request): ?array
    {
        $location = $request->session()->get('delivery_location');

        if (
            !is_array($location) ||
            !isset($location['latitude']) ||
            !isset($location['longitude'])
        ) {
            return null;
        }

        if (
            !is_numeric($location['latitude']) ||
            !is_numeric($location['longitude'])
        ) {
            return null;
        }

        return [
            'latitude' => (float) $location['latitude'],
            'longitude' => (float) $location['longitude'],
        ];
    }

    public function set(
        Request $request,
        float $latitude,
        float $longitude
    ): void {
        $request->session()->put('delivery_location', [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget('delivery_location');
    }

    public function latitude(Request $request): ?float
    {
        return $this->get($request)['latitude'] ?? null;
    }

    public function longitude(Request $request): ?float
    {
        return $this->get($request)['longitude'] ?? null;
    }

    public function exists(Request $request): bool
    {
        return $this->get($request) !== null;
    }
}