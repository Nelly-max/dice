<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HouseOnSaleUnitTypeImage extends Model
{
    use SoftDeletes;

    protected $connection = 'homecity';

    protected $table = 'house_on_sale_unit_type_images';

    protected $fillable = [
        'property_id',
        'house_on_sale_unit_type_id',
        'image_path',
        'label',
    ];

    /*
    |----------------------------------------------------------
    | RELATION: Property
    |----------------------------------------------------------
    */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /*
    |----------------------------------------------------------
    | OPTIONAL: Unit Type (if you have a model for it)
    |----------------------------------------------------------
    */
    public function unitType()
    {
        return $this->belongsTo(
            HouseUnitType::class,
            'house_on_sale_unit_type_id'
        );
    }
}