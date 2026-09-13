<?php

namespace App\Models\Hub;

use Illuminate\Database\Eloquent\Model;

class RiderCarDetail extends Model
{
    /**
     * Database connection
     */
    protected $connection = 'customer';


    /**
     * Table name
     */
    protected $table = 'rider_car_details';



    /**
     * Mass assignable fields
     */
    protected $fillable = [

        'rider_id',

        'vehicle_type',

        'vehicle_make',

        'vehicle_model',

        'plate_number',

        'logbook_photo',

        'front_photo',

        'side_photo',

        'back_photo',

        'verification_status',

        'rejection_reason',

        'verified_at',

    ];



    /**
     * Attribute casting
     */
    protected $casts = [

        'verified_at' => 'datetime',

    ];



    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /**
     * Vehicle belongs to rider
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
    | Scopes
    |--------------------------------------------------------------------------
    */


    /**
     * Pending vehicle verification
     */
    public function scopePending($query)
    {
        return $query->where(
            'verification_status',
            'pending'
        );
    }



    /**
     * Approved vehicles
     */
    public function scopeApproved($query)
    {
        return $query->where(
            'verification_status',
            'approved'
        );
    }



    /**
     * Check verification status
     */
    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

}