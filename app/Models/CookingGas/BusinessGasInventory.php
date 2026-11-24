<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Model;

class BusinessGasInventory extends Model
{
    /**
     * Cooking Gas subdivision database
     */
    protected $connection = 'cookinggas';

    /**
     * Table name
     */
    protected $table = 'business_gas_inventory';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'business_id',
        'gas_cylinder_id',
        'gas_quantity_id',
        'total_cylinders',
        'filled_cylinders',
        'refill_price',
        'complete_price',
    ];

    // Relationships
    public function cylinder()
    {
        return $this->belongsTo(GasCylinder::class, 'gas_cylinder_id');
    }

    public function quantity()
    {
        return $this->belongsTo(GasQuantity::class, 'gas_quantity_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function gasCylinder()
    {
        return $this->belongsTo(GasCylinder::class, 'gas_cylinder_id');
    }

    public function gasQuantity()
    {
        return $this->belongsTo(GasQuantity::class, 'gas_quantity_id');
    }

    public function getCheckoutPriceAttribute()
    {
        return $this->refill_price;
    }

    // ============================================
    // CART-SPECIFIC METHODS
    // ============================================

    public static function resolveById($id)
    {
        return static::on('cookinggas')
            ->with(['business', 'gasCylinder', 'gasQuantity'])
            ->find($id);
    }

    public function getBusinessAccount()
    {
        return $this->business->account ?? null;
    }

    /**
     * Get display name for cart
     */
    public function getCartDisplayName()
    {
        $brand = $this->cylinder->brand_name ?? 'Gas Cylinder';
        $quantity = $this->quantity->quantity ?? '';
        $unit = $this->quantity->unit ?? 'kg';
        
        return "{$brand} ({$quantity})";
    }

    /**
     * Get price for cart (use refill price by default)
     */
    public function getCartPrice()
    {
        return $this->refill_price ?? 0;
    }

    /**
     * Get image with quantity applied
     */
    public function getCartImage($quantity = 1)
    {
        $image = GasQntImage::where('gas_cylinder_id', $this->gas_cylinder_id)
            ->where('quantity_id', $this->gas_quantity_id)
            ->value('image_path');

        // same address as the gas cards (MEDIA_URL / image_path); had an extra "/media/"
        if (!$image) {
            return null;   // the cart shows an image icon (there is no img/placeholder.png)
        }

        return rtrim((string) config('app.media_url'), '/') . '/' . ltrim($image, '/');
    }
    /**
     * Get business name
     */
    public function getBusinessName()
    {
        return $this->business->name ?? 'Unknown Seller';
    }

    /**
     * Get subdivision code for cart
     */
    public function getSubdivisionCode()
    {
        return 'cooking_gas';
    }

    public function availableStock(): int
    {
        return (int) $this->filled_cylinders;
    }

    /**
     * Get stock ID for cart reference
     * This is the ID that will be stored in cart.stockable_id
     */
    public function getStockableId()
    {
        return $this->id;
    }

    /**
     * Get stockable type for cart reference
     * This is the class that will be stored in cart.stockable_type
     */
    public function getStockableType()
    {
        return self::class; // Returns 'App\Models\CookingGas\BusinessGasStock'
    }

    /**
     * Refills a buyer may see and order — the Home Market rules (App\Services\CookingGas\GasCatalog):
     * subscribed shop with e-commerce, open, the cart's gas shop or within the delivery radius,
     * and filled cylinders in stock.
     */
    public function scopeSellable($query)
    {
        return $query
            ->whereIn('business_id', app(\App\Services\CookingGas\GasCatalog::class)->visibleIds() ?: [0])
            ->where('filled_cylinders', '>', 0);
    }
}
