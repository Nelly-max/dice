<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $connection = 'hub';
    
    use HasFactory;

    // Explicitly define the correct table name
    protected $table = 'physical_countries';

    protected $fillable = ['name', 'code'];

    /**
     * A country has many counties.
     */
    public function counties()
    {
        return $this->hasMany(PhysicalCounty::class, 'country_id');
    }
}
