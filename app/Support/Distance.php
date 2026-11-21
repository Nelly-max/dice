<?php

namespace App\Support;

/**
 * Straight-line distance between two points (Haversine), and the radius
 * steps visitors can pick on Home City / Airbnb (500 m … 5 km).
 */
class Distance
{
    /** Radius choices in metres, smallest first. */
    public const RADII = [500, 1000, 2000, 3000, 5000];

    /** @deprecated pages now default to any distance */
    public const DEFAULT_RADIUS = 5000;

    public static function km(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?float
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }

        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * The radius from ?radius=… (metres), or null for "any distance".
     * Pages open on every listing; the radius only applies once the visitor picks one.
     */
    public static function radiusFromRequest(?string $value): ?int
    {
        $value = (int) $value;

        return in_array($value, self::RADII, true) ? $value : null;
    }

    public static function label(int $metres): string
    {
        return $metres < 1000 ? $metres . ' m' : ($metres / 1000) . ' km';
    }
}
