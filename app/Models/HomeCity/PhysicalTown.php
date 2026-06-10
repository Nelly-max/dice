<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalTown extends Model
{
    protected $connection = 'hub';
    
    use HasFactory;

    protected $table = 'physical_towns'; // 👈 match your DB table name

    protected $fillable = ['county_id', 'name'];

    public function county()
    {
        return $this->belongsTo(PhysicalCounty::class, 'county_id');
    }

    public function places()
    {
        return $this->hasMany(PhysicalPlace::class, 'town_id');
    }
}
