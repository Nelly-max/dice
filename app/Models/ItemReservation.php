<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemReservation extends Model
{
    protected $fillable = [
        'cart_id',
        'stockable_type',
        'stockable_id',
        'quantity',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}