<?php

namespace App\Models\Customer;
use App\Models\Subdivision; 

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

class Cart extends Model
{
    protected $connection = 'customer';

    protected $table = 'cart';
    
    protected $fillable = [
        'user_id',
        'session_id',

        'business_account',

        'subdivision_code',
        'sub_division_id',

        'stockable_id',
        'stockable_type',

        'quantity',
        'shipment_type_id',
    ];
    
    protected $casts = [
        'delivery_time' => 'datetime'
    ];
    
    // Polymorphic relationship to the actual product    
    public function stockable()
    {
        return $this->morphTo(__FUNCTION__, 'stockable_type', 'stockable_id');
    }
    // Relationship to subdivision in hub database
    public function subdivision()
    {
        return $this->belongsTo(Subdivision::class, 'sub_division_id');
    }
    
    // ============================================
    // ACCESSORS - Fetch product data dynamically
    // ============================================

    /**
     * Check for active items
     */
    public function scopeActive($query)
    {
        return $query->where(
            'updated_at',
            '>',
           now()->subDays((int) config('smartmarket.cart_keep_days', 30))
        );
    }
    
    /**
     * Get product name from the actual product table
     */
    public function getProductNameAttribute()
    {
        return $this->stockable
            ? $this->stockable->getCartDisplayName()
            : 'Product not found';
    }

    public function getPriceAttribute()
    {
        return $this->stockable
            ? $this->stockable->getCartPrice()
            : 0;
    }


    public function getDisplayImageAttribute()
    {
        if ($this->stockable &&
            method_exists($this->stockable, 'getCartImage')) {

            return $this->stockable->getCartImage();
        }

        return null;   // no photo: the cart shows an image icon (there is no img/placeholder.png)
    }

    public function getBusinessNameAttribute()
    {
        return $this->stockable
            ? $this->stockable->getBusinessName()
            : null;
    }
    
    /**
     * Calculate total for this cart item
     */
    public function getTotalAttribute()
    {
        return $this->price * $this->quantity;
    }
    
    // ============================================
    // SCOPES
    // ============================================
    
    /**
     * Scope for current user/session
     */
    
    public function scopeForCurrent($query)
    {
        $userId = auth('customer')->id();
        $sessionId = session()->getId();

        return $query
            ->active()
            ->where(function ($q) use ($userId, $sessionId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            });
    }

    /**
     * Scope for specific subdivision
     */
    public function scopeForSubdivision($query, $subdivisionCode)
    {
        return $query->where('subdivision_code', $subdivisionCode);
    }
    
    /**
     * Scope for cooking gas items only
     */
    public function scopeCookingGas($query)
    {
        return $query->where('subdivision_code', 'cooking_gas')
                     ->where('stockable_type', 'App\Models\CookingGas\BusinessGasStock');
    }
    
    /**
     * Eager load product data
     */
    public function scopeWithProduct($query)
    {
        return $query->with('stockable');
    }
    
    /**
     * Calculate cart subtotal
     */
    public function scopeSubtotal($query)
    {
        return $query->get()->sum(function($item) {
            return $item->total;
        });
    }
    
    // ============================================
    // HELPER METHODS
    // ============================================

}