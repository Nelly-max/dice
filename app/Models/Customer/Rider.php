<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    /**
     * The database connection used by the model.
     *
     * @var string
     */
    protected $connection = 'customer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'rider_account',
        'national_id',
        'license_number',
        'name',
        'phone',
        'email',
        'status',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Rider personal documents.
     */
    // public function personalDetails()
    // {
    //     return $this->hasOne(RiderPersonalDetail::class);
    // }

    /**
     * Rider vehicle details.
     */
    // public function carDetails()
    // {
    //     return $this->hasOne(RiderCarDetail::class);
    // }

    /**
     * Rider current location.
     */
    // public function location()
    // {
    //     return $this->hasOne(RiderLocation::class);
    // }

    /**
     * Deliveries assigned to the rider.
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isBusy(): bool
    {
        return $this->status === 'busy';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */

    public const ONLINE = 'online';
    public const OFFLINE = 'offline';
    public const BUSY = 'busy';
}