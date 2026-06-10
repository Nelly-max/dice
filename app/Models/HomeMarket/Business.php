<?php

namespace App\Models\HomeMArket;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    /**
     * Database connection for Cooking Gas subdivision
     */
    protected $connection = 'homemarket';

    /**
     * Table name
     */
    protected $table = 'business';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'hub_business_account_id',

        // Business identity
        'name',
        'branch',

        // Contacts
        'phone',
        'email',

        // Location
        'county_id',
        'town_id',
        'place_id',
        'latitude',
        'longitude',

        // Operations
        'open_days',
        'closed_days',
        'opening_time',
        'closing_time',
        'shop_status',

        // Lifecycle
        'created_at',
        'activated_at',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'open_days'     => 'array',
        'closed_days'   => 'array',
        'opening_time'  => 'datetime:H:i',
        'closing_time'  => 'datetime:H:i',
        'activated_at'  => 'datetime',
    ];

    /**
     * Default attribute values
     */
    protected $attributes = [
        'branch'      => 'Main Branch',
        'shop_status' => 'closed',
    ];



    /**
     * Scope: business has an ACTIVE subscription
     * with ecommerce feature enabled
     */
    public function scopeEcommerceEnabled($query) {
        return $query->whereHas('subscription', function ($sub) {
            $sub->where('status', 'active')->whereHas('tariff', function ($tf) {
                $tf->where('ecommerce_access', 1);
            });
        });
    }
}
