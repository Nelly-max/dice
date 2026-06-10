<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;

class CommercialUnit extends Model
{
    protected $connection = 'homecity';

    protected $table = 'commercial_units';

    protected $fillable = [
        'property_id',
        'unit_number',
        'size',
        'status',
        'remaining_space',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'size' => 'decimal:2',
        'remaining_space' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Helpers (Very useful for UI)
    |--------------------------------------------------------------------------
    */

    public function getIsAvailableAttribute()
    {
        return $this->status === 'Vacant' || $this->status === 'Partially_Occupied';
    }

    public function getDisplayLabelAttribute()
    {
        return $this->unit_number . ' (' . $this->size . ' sqm)';
    }
}