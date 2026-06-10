<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;

class LandUnit extends Model
{
    protected $connection = 'homecity';

    protected $table = 'land_units';

    protected $fillable = [
        'property_id',
        'land_measure_unit_id',
        'size',
        'price',
        'unit_count',
        'available_count',
        'lease_period',
        'lease_period_measure',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Property relationship
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Measurement unit (e.g. acres, hectares, sqft, etc.)
    public function measureUnit()
    {
        return $this->belongsTo(LandMeasureUnit::class, 'land_measure_unit_id');
    }
}