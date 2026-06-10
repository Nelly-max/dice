<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;
    protected $connection = 'homecity';

    protected $fillable = [
        'title',
        'listing_type',
        'description',
        'property_category_id',
        'house_property_type_id',
        'lister_id',
        'charges',
    ];

    protected $casts = [
        'charges' => 'array',
    ];

    /* ============================================================
     |  🔹 RELATIONSHIPS
     * ============================================================ */

    /** Category */
    public function category()
    {
        return $this->belongsTo(PropertyCategory::class, 'property_category_id');
    }

    /** Type */
    public function type()
    {
        return $this->belongsTo(HousePropertyType::class, 'house_property_type_id');
    }

    /**  Property belongs to a location */
    public function location()
    {
        return $this->hasOne(PropertyLocation::class, 'property_id');
    }


    public function images()
    {
        return $this->hasMany(HouseUnitTypeImage::class, 'house_unit_type_id');
    }


    /** Amenities */
    public function amenities()
    {
        return $this->belongsToMany(Amenity::class, 'property_amenity', 'property_id', 'amenity_id');
    }

    /** Lister */
    public function lister()
    {
        return $this->belongsTo(Lister::class, 'lister_id');
    }

    public function extraCharges()
    {
        return $this->hasMany(PropertyExtraCharge::class, 'property_id');
    }

    public function bills()
    {
        return $this->hasMany(PropertyBill::class, 'property_id');
    }

    public function billType()
    {
        return $this->hasMany(BillType::class, 'bill_id');
    }





    public function houseUnits()
    {
        return $this->hasMany(HouseUnit::class, 'property_id');
    }

    public function houseOnSaleUnits()
    {
        return $this->hasMany(HouseOnSaleUnit::class, 'property_id');
    }

    public function houseOnSaleUnitTypeImages()
    {
        return $this->hasMany(HouseOnSaleUnitTypeImage::class);
    }

    public function hostelUnits()
    {
        return $this->hasMany(HostelUnit::class);
    }

    public function hostelImages()
    {
        return $this->hasManyThrough(
            HostelImage::class,
            Hostel::class,
            'property_id', // Foreign key on hostels table
            'hostel_id',   // Foreign key on hostel_images table
            'id',          // Local key on properties
            'id'           // Local key on hostels
        );
    }

    
    public function officeUnits()
    {
        return $this->hasMany(OfficeUnit::class, 'property_id');
    }

    public function officeImages()
    {
        return $this->hasMany(OfficeImage::class, 'property_id');
    }

    public function shopUnits()
    {
        return $this->hasMany(ShopUnit::class, 'property_id');
    }

    public function shopImages()
    {
        return $this->hasMany(ShopImage::class, 'property_id');
    }


    public function containers()
    {
        return $this->hasMany(Container::class);
    }

    public function containerImages()
    {
        return $this->hasMany(ContainerImage::class, 'property_id');
    }

    public function houseOnSaleTypeImages()
    {
        return $this->hasMany(HouseOnSaleUnitTypeImage::class, 'property_id');
    }

    
    public function landUnits()
    {
        return $this->hasMany(LandUnit::class, 'property_id');
    }

    public function landImages()
    {
        return $this->hasMany(LandImage::class);
    }

    public function warehouseUnits()
    {
        return $this->hasMany(WarehouseUnit::class);
    }

    public function warehouseType()
    {
        return $this->belongsTo(WarehousePropertyType::class, 'warehouse_property_type_id');
    }


    /* ============================================================
     |  🔹 CUSTOM ACCESSORS & LOGIC
     * ============================================================ */

    /** Price range for vacant units */
    public function getVacantPriceRange()
    {
        $vacantPrices = $this->units()
            ->where('status', 'Vacant')
            ->pluck('price');

        if ($vacantPrices->isEmpty()) {
            return null;
        }

        $min = $vacantPrices->min();
        $max = $vacantPrices->max();

        return $min === $max
            ? ['label' => 'Ksh ' . number_format($min)]
            : ['label' => 'Ksh ' . number_format($min) . ' - Ksh ' . number_format($max)];
    }

    /** Vacant unit types names */
    public function getVacantUnitTypes()
    {
        return $this->units()
            ->where('status', 'Vacant')
            ->with('type')
            ->get()
            ->pluck('type.name')
            ->unique()
            ->values();
    }

    /** Formatted location: "County, Town" */
    public function getFormattedLocationAttribute()
    {
        if (!$this->location) {
            return 'Location not set';
        }

        $county = $this->location->county->name ?? null;
        $town   = $this->location->town->name ?? null;
        $place  = $this->location->place->name ?? null;

        return collect([$county, $town, $place])
            ->filter()
            ->implode(', ') ?: 'Location not set';
    }
}
