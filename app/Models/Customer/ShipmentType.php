<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ShipmentType extends Model
{
    protected $connection = 'customer';
    
    protected $table = 'shipment_types';
    
    protected $fillable = [
        'name',
        'min_amount',

        'time1',
        'time2',
    ];

}