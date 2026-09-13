<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class ItemReservation extends Model
{
    protected $connection = 'customer';
    
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

    public function scopeActive($query)
    {
        return $query->where(
            'updated_at',
            '>',
            now()->subMinutes(10)
        );
    }
}