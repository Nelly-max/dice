<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ShipmentType extends Model
{
    protected $table = 'shipment_types';
    
    protected $fillable = [
        'name',
        'min_amount',

        'time1',
        'time2',
    ];

}