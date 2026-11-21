<?php


namespace App\Models\GlamRoom;

use Illuminate\Database\Eloquent\Model;

class ProductItemImage extends Model
{
    protected $connection = 'glamroom';
    protected $table = 'product_item_images';
    protected $fillable = [
        'product_item_id', 'image_path'
    ];



}
