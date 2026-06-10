<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $connection = 'homecity';

    protected $table = 'containers';

    protected $fillable = [
        'property_id',
        'size',
        'units_available',
        'price',
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
        'price' => 'decimal:2',
        'units_available' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Helpers (Useful for UI)
    |--------------------------------------------------------------------------
    */

    public function getIsAvailableAttribute()
    {
        return $this->units_available > 0;
    }

    public function getDisplayLabelAttribute()
    {
        return $this->size . ' Container';
    }
}