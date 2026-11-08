<?php


namespace App\Models\FarmMarket;

use Illuminate\Database\Eloquent\Model;

class ProductItemImage extends Model
{
    protected $connection = 'farmmarket';
    protected $table = 'product_item_images';
    protected $fillable = [
        'product_item_id', 'image_path'
    ];



}
