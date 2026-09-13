<?php

namespace App\Models\Hub;

use Illuminate\Database\Eloquent\Model;

class RiderPersonalDetail extends Model
{
    /**
     * Database connection
     */
    protected $connection = 'customer';


    /**
     * Table name
     */
    protected $table = 'rider_personal_details';


    /**
     * Mass assignable fields
     */
    protected $fillable = [

        'rider_id',

        'passport_photo',

        'id_front_photo',

        'id_back_photo',

        'license_photo',

    ];



    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /**
     * Belongs to rider
     */
    public function rider()
    {
        return $this->belongsTo(
            Rider::class,
            'rider_id'
        );
    }


}