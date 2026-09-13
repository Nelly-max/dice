<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer\Rider;
use App\Models\Customer\OrderBusiness;

class Delivery extends Model
{
    protected $connection = 'customer';

    protected $fillable = [
        'delivery_number',
        'order_id',
        'business_account',

        'rider_account',

        'pickup_name',
        'pickup_phone',
        'pickup_latitude',
        'pickup_longitude',

        'customer_name',
        'customer_phone',
        'dropoff_latitude',
        'dropoff_longitude',

        'distance_km',
        'eta_minutes',

        'dispatch_attempts',
        'dispatch_status',
        'next_retry_at',

        'status',

        'assigned_at',
        'arrived_shop_at',
        'picked_up_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'pickup_latitude'     => 'decimal:7',
        'pickup_longitude'    => 'decimal:7',
        'dropoff_latitude'    => 'decimal:7',
        'dropoff_longitude'   => 'decimal:7',
        'distance_km'         => 'decimal:2',

        'assigned_at'         => 'datetime',
        'arrived_shop_at'     => 'datetime',
        'picked_up_at'        => 'datetime',
        'delivered_at'        => 'datetime',
        'completed_at'        => 'datetime',
        'cancelled_at'        => 'datetime',
        'next_retry_at'       => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function rider()
    {
        return $this->hasOne(Rider::class, 'account', 'rider_account');
    }

    public function order()
    {
        return $this->belongsTo(OrderBusiness::class, 'order_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAssigned(): bool
    {
        return !empty($this->rider_account);
    }

    public function canRetry(): bool
    {
        return in_array($this->dispatch_status, [
            'pending',
            'retrying',
        ]);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            'completed',
            'delivered',
        ]);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}