<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalPlace extends Model
{
    protected $connection = 'hub';
    
    use HasFactory;

    protected $table = 'physical_places'; // 👈 Important line

    protected $fillable = ['town_id', 'name'];

    public function town()
    {
        return $this->belongsTo(PhysicalTown::class, 'town_id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'location_id');
    }
}

