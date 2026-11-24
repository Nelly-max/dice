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
        'discount',
        'discount_start',
        'discount_stop',
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

    public function getCheckoutPriceAttribute()
    {
        return $this->retail_price;
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


    public function getCartPrice()
    {
        return $this->retail_price ?? 0;
    }


    /**
     * Cart photo: the same address as the product cards (ProductPresenter::imageUrl):
     * MEDIA_URL / homemarket.product_image_prefix / image_path. (Had an extra "/media/" that the
     * cards don't use, so cart photos pointed at files that don't exist.)
     */
    public function getCartImage()
    {
        $base = rtrim((string) config('app.media_url'), '/');
        $image = $this->item?->images()->value('image_path');

        if (!$image) {
            return $base . '/' . ltrim((string) config('homemarket.placeholder_image', 'img/homeMarket/products/item_image.png'), '/');
        }

        return $base . '/' . config('homemarket.product_image_prefix') . ltrim($image, '/');
    }


    public function getProductNameAttribute()
    {
        return $this->stockable
            ? ($this->stockable->product_name
                ?? $this->stockable->name
                ?? $this->stockable->title
                ?? 'Product Item')
            : 'Product not found';
    }

    public function availableStock(): int
    {
        return (int) $this->stock_available;
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