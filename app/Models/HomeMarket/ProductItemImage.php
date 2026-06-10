<?php


namespace App\Models\HomeMarket;

use Illuminate\Database\Eloquent\Model;

class ProductItemImage extends Model
{
    protected $connection = 'homemarket';
    protected $table = 'product_item_images';
    protected $fillable = [
        'product_item_id', 'image_path'
    ];



}
