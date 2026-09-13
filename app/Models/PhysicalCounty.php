<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Country;

class PhysicalCounty extends Model
{
    use HasFactory;

    protected $table = 'physical_counties'; 

    protected $fillable = ['country_id', 'name'];

}
