<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;

class WarehousePropertyType extends Model
{
    protected $connection = 'homecity';

    protected $table = 'warehouse_property_types';

    protected $fillable = [
        'name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function warehouses()
    {
        return $this->hasMany(WarehouseUnit::class, 'warehouse_property_type_id');
    }
}