<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use HasFactory;

    protected $connection = 'homecity';

    protected $table = 'listings';

    protected $fillable = [
        'lister_id',
        'property_id',
        'status',
        'description',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /*
    |---------------------------------------------------------
    | Relationships
    |---------------------------------------------------------
    */

    /**
     * Listing belongs to a Property
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function houseUnitType()
    {
        // Points to the house_unit_types table
        return $this->belongsTo(HouseUnitType::class, 'house_unit_type_id');
    }

    public function lister()
    {
        return $this->belongsTo(\App\Models\User::class, 'lister_id');
    }

    /**
     * Listing belongs to a Business (via account)
     */
    

    /*
    |---------------------------------------------------------
    | Scopes (optional but useful)
    |---------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBooked($query)
    {
        return $query->where('status', 'booked');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeForBusiness($query, $account)
    {
        return $query->where('lister_id', $account);
    }

    /*
    |---------------------------------------------------------
    | Helpers
    |---------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isBooked(): bool
    {
        return $this->status === 'booked';
    }

    public function isSold(): bool
    {
        return $this->status === 'sold';
    }
}