<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Model;

/** A container type, e.g. name "Soft Plastic", capacity "10Liters" (or a truck, "10000Liters"). */
class WaterContainer extends Model
{
    protected $connection = 'water';

    protected $table = 'water_containers';

    protected $guarded = [];

    /** Capacity in litres from strings like "10Liters", "18.9 L", "500ml", "10 m3" (null when unknown). */
    public function litres(): ?float
    {
        $raw = strtolower(str_replace(',', '', (string) $this->capacity));

        if (!preg_match('/(\d+(?:\.\d+)?)\s*([a-z³0-9]*)/', $raw, $m)) {
            return null;
        }

        $n = (float) $m[1];
        $unit = $m[2];

        return match (true) {
            str_starts_with($unit, 'ml') => $n / 1000,
            str_starts_with($unit, 'm3'), str_starts_with($unit, 'm³'), str_starts_with($unit, 'cub') => $n * 1000,
            default => $n,
        };
    }

    /** "10 L", "500 ml", "10,000 L" — or the raw capacity text. */
    public function sizeLabel(): string
    {
        $l = $this->litres();

        if ($l === null) {
            return trim((string) $this->capacity);
        }

        if ($l < 1) {
            return round($l * 1000) . ' ml';
        }

        return number_format($l, fmod($l, 1) ? 1 : 0) . ' L';
    }

    /**
     * Water trucks / tankers (clean water for estates): named so, or 1,000 litres and up.
     */
    public function isTruck(): bool
    {
        if (preg_match('/truck|tanker|bowser|bulk|lorry/i', (string) $this->name)) {
            return true;
        }

        return ($this->litres() ?? 0) >= (float) config('water.truck_min_litres', 1000);
    }
}
