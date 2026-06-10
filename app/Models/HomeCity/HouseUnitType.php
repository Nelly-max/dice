<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HouseUnitType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * A unit type can have many units.
     */
    public function units()
    {
        return $this->hasMany(HouseUnit::class, 'house_unit_type_id');
    }
    public function images()
    {
        return $this->hasMany(HouseUnitTypeImage::class, 'house_unit_type_id');
    }
    

}
