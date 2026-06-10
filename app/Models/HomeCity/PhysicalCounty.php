<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalCounty extends Model
{
    protected $connection = 'hub';
    
    use HasFactory;

    protected $table = 'physical_counties'; // 👈 matches your migration name

    protected $fillable = ['country_id', 'name'];

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }


    public function towns()
    {
        return $this->hasMany(PhysicalTown::class, 'county_id');
    }
}
