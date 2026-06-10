<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;

class LandMeasureUnit extends Model
{
    protected $connection = 'homecity';

    protected $table = 'land_measure_units';

    protected $fillable = [
        'name',
        'abbreviation',
        'conversion_to_sqm',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function landUnits()
    {
        return $this->hasMany(LandUnit::class, 'land_measure_unit_id');
    }

    public function images()
    {
        return $this->hasMany(LandImage::class, 'land_measure_unit_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'conversion_to_sqm' => 'decimal:4',
    ];
}