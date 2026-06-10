<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HouseUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_number',
        'price',
        'status',
        'house_unit_type_id',
        'property_id',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function type()
    {
        return $this->belongsTo(HouseUnitType::class, 'house_unit_type_id');
    }

}
