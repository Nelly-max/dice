<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseUnitTypeImage extends Model
{
    protected $connection = 'homecity';
    protected $table = 'house_unit_type_images';
    
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'house_unit_type_id',
        'image_path',
        'label',
    ];

    // Belongs to a property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Optional: Belongs to a unit type
    public function unitType()
    {
        return $this->belongsTo(HouseUnitType::class, 'house_unit_type_id');
    }
}
