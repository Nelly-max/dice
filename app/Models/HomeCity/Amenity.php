<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function properties()
        {
            return $this->belongsToMany(Property::class, 'property_amenity', 'amenity_id', 'property_id');
        }

}
