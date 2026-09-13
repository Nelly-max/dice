<?php

namespace App\Models\Hub;

use Illuminate\Database\Eloquent\Model;

class Rider extends Model
{
    /**
     * Database connection
     */
    protected $connection = 'customer';


    /**
     * Table name
     */
    protected $table = 'riders';


    /**
     * Mass assignable fields
     */
    protected $fillable = [

        // Account
        'customer_id',
        'rider_account',

        // Identification
        'national_id',
        'license_number',

        // Personal details
        'name',
        'date_of_birth',
        'phone',
        'email',

        // Payment
        'mpesa_number',

        // Locality
        'county_id',
        'town_id',
        'place_id',

        // Status
        'account_status',

    ];



    /**
     * Casts
     */
    protected $casts = [

        'date_of_birth' => 'date',

    ];



    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /**
     * Rider documents
     */
    public function personalDetails()
    {
        return $this->hasOne(
            RiderPersonalDetail::class,
            'rider_id'
        );
    }



    /**
     * Rider vehicle details
     */
    public function carDetails()
    {
        return $this->hasOne(
            RiderCarDetail::class,
            'rider_id'
        );
    }




    /**
     * Check if rider can receive deliveries
     */
    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }



    /**
     * Scope active riders
     */
    public function scopeActive($query)
    {
        return $query->where(
            'account_status',
            'active'
        );
    }



    /**
     * Scope pending riders
     */
    public function scopePending($query)
    {
        return $query->where(
            'account_status',
            'pending'
        );
    }
}