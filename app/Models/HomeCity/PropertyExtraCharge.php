<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyExtraCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'extra_charge_id',
        'amount',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function extraCharge()
    {
        return $this->belongsTo(ExtraCharge::class, 'extra_charge_id');
    }
}
