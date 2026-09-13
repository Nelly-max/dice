<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class DeliveryRiderRequest extends Model
{
    protected $connection = 'customer';

    protected $table = 'delivery_rider_requests';


    protected $fillable = [

        'request_number',

        'delivery_id',

        'rider_id',

        'status',

        'sent_at',

        'expires_at',

        'responded_at',

        'distance_km',

        'eta_minutes',

    ];



    protected $casts = [

        'sent_at' => 'datetime',

        'expires_at' => 'datetime',

        'responded_at' => 'datetime',

        'distance_km' => 'decimal:2',

        'eta_minutes' => 'integer',

    ];



    /*
    |--------------------------------------------------------------------------
    | Delivery Relationship
    |--------------------------------------------------------------------------
    */

    public function delivery()
    {
        return $this->belongsTo(
            Delivery::class,
            'delivery_id'
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Rider Relationship
    |--------------------------------------------------------------------------
    */

    public function rider()
    {
        return $this->belongsTo(
            Rider::class,
            'rider_id'
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Status Scopes
    |--------------------------------------------------------------------------
    */


    public function scopeSent($query)
    {
        return $query->where(
            'status',
            'pending'
        );
    }



    public function scopeAccepted($query)
    {
        return $query->where(
            'status',
            'accepted'
        );
    }



    public function scopeExpired($query)
    {
        return $query->where(
            'status',
            'expired'
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    public function accept()
    {
        return $this->update([

            'status' => 'accepted',

            'responded_at' => now(),

        ]);
    }



    public function reject()
    {
        return $this->update([

            'status' => 'rejected',

            'responded_at' => now(),

        ]);
    }



    public function expire()
    {
        return $this->update([

            'status' => 'expired',

            'responded_at' => now(),

        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | Check if rider can still respond
    |--------------------------------------------------------------------------
    */


    public function isExpired(): bool
    {
        return now()->greaterThan(
            $this->expires_at
        );
    }

}