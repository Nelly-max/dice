<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousePropertyType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function properties()
    {
        return $this->hasMany(Property::class, 'property_type_id');
    }
}
