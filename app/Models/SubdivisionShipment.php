<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SubdivisionShipment extends Model
{
    protected $table = 'subdivision_shipment';
    
    protected $fillable = [
        'subdivision_id',
        'consignment',
    ];

}