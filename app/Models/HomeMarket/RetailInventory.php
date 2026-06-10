<?php

namespace App\Models\HomeMarket;

use Illuminate\Database\Eloquent\Model;

class RetailInventory extends Model
{
    protected $connection = 'homemarket';

    protected $table = 'retail_inventory';

    protected $fillable = [
        'business_account',
        'product_id',
        'item_id',
        'stock_count',
        'retail_price',
        'status',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function item()
    {
        return $this->belongsTo(ProductItem::class, 'item_id');
    }

    // ============================================
    // CART-SPECIFIC METHODS
    // ============================================

    public static function resolveById($id)
    {
        return static::on('homemarket')
            ->with(['business','product','item'])
            ->find($id);
    }

//     public static function resolveById($id)
// {
//     return static::with([
//         'business',
//         'product',
//         'item'
//     ])->find($id);
// }

    public function getCartPrice()
    {
        return $this->retail_price ?? 0;
    }

    // public function getCartImage($quantity = 1)
    // {
    //     // Get base image from GasQntImage
    //     $image = \App\Models\CookingGas\GasQntImage::where('gas_cylinder_id', $this->gas_cylinder_id)
    //         ->where('quantity_id', $this->gas_quantity_id)
    //         ->value('file_path');
        
    //     if (!$image) {
    //         return asset('img/placeholder.png');
    //     }
        
    //     // Apply quantity to image path
    //     return $this->applyQuantityToImage($image, $quantity);
    // }

    // public function getCartImage()
    // {
    //     return $this->item?->images?->first()?->image_path;
    // }

public function getCartImage()
{
    $image = $this->item?->images()->value('image_path');

    if (!$image) {
        return rtrim(config('app.media_url'), '/') . '/media/img/homeMarket/products/item_image.png';
    }

    return rtrim(config('app.media_url'), '/') . '/media/' . ltrim($image, '/');
}

// public function getCartImage()
// {
//     dd([
//         'item_id' => $this->item?->id,
//         'images_count' => $this->item?->images()->count(),
//         'first_image' => $this->item?->images()->first(),
//     ]);
// }

    public function getProductNameAttribute()
    {
        return $this->stockable
            ? ($this->stockable->product_name
                ?? $this->stockable->name
                ?? $this->stockable->title
                ?? 'Product Item')
            : 'Product not found';
    }

    public function getCartDisplayName()
    {
        $productName = $this->item?->product?->name ?? 'Product Item';

        $sizeValue = $this->item?->size_value;
        $unit = $this->item?->quantityUnit?->slug;

        $packaging = $this->item?->packaging?->name;

        $parts = [];

        if ($sizeValue && $unit) {
            $parts[] = "{$sizeValue} {$unit}";
        }

        if ($packaging) {
            $parts[] = $packaging;
        }

        if (!empty($parts)) {
            return $productName . ' (' . implode(', ', $parts) . ')';
        }

        return $productName;
    }

    public function getBusinessName()
    {
        return $this->business->name ?? 'Unknown Seller';
    }

    public function getBusinessAccount()
    {
        return $this->business_account
            ?? $this->business->account
            ?? null;
    }
}