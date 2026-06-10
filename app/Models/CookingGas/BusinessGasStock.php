<?php

namespace App\Models\CookingGas;

use Illuminate\Database\Eloquent\Model;

class BusinessGasStock extends Model
{
    /**
     * Cooking Gas subdivision database
     */
    protected $connection = 'cookinggas';

    /**
     * Table name
     */
    protected $table = 'business_gas_stock';

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
    $image = \App\Models\CookingGas\GasQntImage::where('gas_cylinder_id', $this->gas_cylinder_id)
        ->where('quantity_id', $this->gas_quantity_id)
        ->value('file_path');

    if (!$image) {
        return rtrim(config('app.media_url'), '/') . '/media/img/placeholder.png';
    }

    return rtrim(config('app.media_url'), '/') . '/media/' . ltrim($image, '/');
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

    /**
     * Apply quantity to image path
     */
    private function applyQuantityToImage($imagePath, $quantity)
    {
        // Your existing logic for quantity-based images
        if (preg_match('/qty_\d+\.\w+$/i', $imagePath)) {
            return preg_replace('/qty_\d+/i', 'qty_' . $quantity, $imagePath);
        }
        
        if (preg_match('/_\d+\.\w+$/i', $imagePath)) {
            $extension = pathinfo($imagePath, PATHINFO_EXTENSION);
            return preg_replace('/_\d+\.\w+$/i', '_' . $quantity . '.' . $extension, $imagePath);
        }
        
        // Add quantity if not present
        $pathInfo = pathinfo($imagePath);
        $extension = $pathInfo['extension'] ?? 'png';
        $filename = $pathInfo['filename'] ?? 'product';
        $dirname = $pathInfo['dirname'] ?? '';
        
        // Construct new path
        $newPath = ($dirname ? $dirname . '/' : '') . $filename . '_' . $quantity . '.' . $extension;
        
        // Use media URL if configured
        if (config('app.media_url')) {
            return config('app.media_url') . '/' . ltrim($newPath, '/');
        }
        
        return asset($newPath);
    }
    
    /**
     * Check if product is available for cart
     */
    public function isAvailableForCart()
    {
        return ($this->filled_cylinders ?? 0) > 0;
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
}