<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyLocation extends Model
{
    use HasFactory;

    // If your table name is singular, specify it
    protected $table = 'property_location';

    protected $fillable = [
        'property_id',
        'county_id',
        'town_id',
        'place_id',
        'street',
        'lane',
        'latitude',
        'longitude',
    ];

    /* ==========================================================
       🔹 RELATIONSHIPS
    ========================================================== */

    // Optional: inverse relation to Property
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function county()
    {
        return $this->belongsTo(PhysicalCounty::class, 'county_id');
    }

    public function town()
    {
        return $this->belongsTo(PhysicalTown::class, 'town_id');
    }

    public function place()
    {
        return $this->belongsTo(PhysicalPlace::class, 'place_id');
    }
}
