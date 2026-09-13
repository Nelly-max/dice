<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SubdivisionShipment extends Model
{
    protected $connection = 'customer';
    
    protected $table = 'subdivision_shipment';
    
    protected $fillable = [
        'subdivision_id',
        'consignment',
    ];

}