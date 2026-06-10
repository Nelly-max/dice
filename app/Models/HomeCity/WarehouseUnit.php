<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;

class WarehouseUnit extends Model
{
    protected $connection = 'homecity';

    protected $table = 'warehouse_units';

    protected $fillable = [
        'property_id',
        'mode',
        'total_space',
        'measure_unit',
        'price',
        'unit_count',
        'lease_period',
        'lease_period_measure',
        'status',
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

    public function images()
    {
        return $this->hasMany(WarehouseUnitImage::class, 'warehouse_unit_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'price' => 'decimal:2',
        'total_space' => 'decimal:2',
        'unit_count' => 'integer',
        'lease_period' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Helpers (VERY IMPORTANT for UI)
    |--------------------------------------------------------------------------
    */

    public function getIsAvailableAttribute()
    {
        return $this->status === 'Available';
    }

    public function getDisplayLabelAttribute()
    {
        if ($this->mode === 'space') {
            return $this->total_space . ' ' . $this->measure_unit . ' Warehouse';
        }

        return $this->unit_count . ' Unit Warehouse';
    }

    public function getPricingLabelAttribute()
    {
        if ($this->mode === 'space') {
            return 'Ksh ' . number_format($this->price) . ' / ' . $this->measure_unit;
        }

        return 'Ksh ' . number_format($this->price) . ' per unit';
    }
}