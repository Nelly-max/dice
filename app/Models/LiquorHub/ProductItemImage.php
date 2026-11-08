<?php


namespace App\Models\LiquorHub;

use Illuminate\Database\Eloquent\Model;

class ProductItemImage extends Model
{
    protected $connection = 'liquorhub';
    protected $table = 'product_item_images';
    protected $fillable = [
        'product_item_id', 'image_path'
    ];



}
