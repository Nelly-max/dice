<?php

namespace App\Models\HomeCity;

use Illuminate\Database\Eloquent\Model;

class ContainerImage extends Model
{
    protected $connection = 'homecity';

    protected $table = 'container_images';

    protected $fillable = [
        'property_id',
        'container_size',
        'image_path',
        'label',
    ];

    /*
    |----------------------------------------------------------
    | RELATIONSHIPS
    |----------------------------------------------------------
    */

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}