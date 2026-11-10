<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $connection = 'homecity';

    protected $table = 'business';

    protected $fillable = [
        'account',
        'hub_business_account_id',
        'subdivision_id',
        'name',
        'branch',
        'main_branch_account',
        'email',
        'phone',
        'logo',
        'county_id',
        'town_id',
        'place_id',
        'latitude',
        'longitude',
        'open_days',
        'closed_days',
        'opening_time',
        'closing_time',
        'shop_status',
        'creation_date',
        'activation_date',
    ];

    protected $casts = [
        'open_days' => 'array',
        'closed_days' => 'array',
        'creation_date' => 'datetime',
        'activation_date' => 'datetime',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
    ];

    /* ============================================================
     |  RELATIONSHIPS
     * ============================================================ */

    /**
     * Properties under this business
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'business_account', 'account');
    }

    /* ============================================================
     |  HUB LOCATION RELATIONSHIPS (cross DB)
     * ============================================================ */

    // public function county()
    // {
    //     return $this->belongsTo(\App\Models\Hub\County::class, 'county_id');
    // }

    // public function town()
    // {
    //     return $this->belongsTo(\App\Models\Hub\Town::class, 'town_id');
    // }

    // public function place()
    // {
    //     return $this->belongsTo(\App\Models\Hub\Place::class, 'place_id');
    // }

    /* ============================================================
     |  ACCESSORS
     * ============================================================ */

    /**
     * Full business location string
     */
    public function getFormattedLocationAttribute()
    {
        return collect([
            optional($this->place)->name,
            optional($this->town)->name,
            optional($this->county)->name,
        ])->filter()->implode(', ') ?: 'N/A';
    }

    /**
     * Status label helper
     */
    public function getIsOpenAttribute()
    {
        return $this->shop_status === 'open';
    }
}