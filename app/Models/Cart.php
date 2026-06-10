<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Cart extends Model
{
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

// public function getDisplayImageAttribute()
// {
//     return $this->stockable
//         ? $this->stockable->getCartImage()
//         : asset('img/placeholder.png');
// }

    public function getDisplayImageAttribute()
    {
        if ($this->stockable &&
            method_exists($this->stockable, 'getCartImage')) {

            return $this->stockable->getCartImage();
        }

        return asset('img/placeholder.png');
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
        return $query->where('user_id', Auth::id());
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
    
    /**
     * Add item to cart
     */
    public static function addItem($stockable, $quantity = 1, $shipmentType = 'quick')
    {
        $userId = Auth::id();
        $sessionId = session()->getId();

        // ============================================
        // CORE IDENTIFIERS
        // ============================================

        $stockableId = $stockable->getStockableId();
        $stockableType = $stockable->getStockableType();

        $businessAccount = null;

        if (method_exists($stockable, 'getBusinessAccount')) {
            $businessAccount = $stockable->getBusinessAccount();
        }

        if (!$businessAccount && isset($stockable->business_account)) {
            $businessAccount = $stockable->business_account;
        }

        $subdivisionCode = $stockable->getSubdivisionCode();

        // ============================================
        // FIND EXISTING ITEM
        // ============================================

        $existing = self::forCurrent()
            ->where('stockable_id', $stockableId)
            ->where('stockable_type', $stockableType)
            ->where('business_account', $businessAccount)
            ->first();

        if ($existing) {
            $existing->quantity += $quantity;
            $existing->shipment_type = $shipmentType;
            $existing->save();

            return $existing;
        }

        // ============================================
        // RESOLVE SUBDIVISION
        // ============================================

        $subDivisionId = null;

        if (!empty($subdivisionCode) && class_exists(Subdivision::class)) {

            $subDivision = Subdivision::where('db_connection', $subdivisionCode)
                ->first();

            $subDivisionId = $subDivision->id ?? null;
        }

        // ============================================
        // CREATE CART ITEM
        // ============================================

        return self::create([
            'user_id' => $userId,
            'session_id' => $sessionId,

            'business_account' => $businessAccount,

            'subdivision_code' => $subdivisionCode,
            'sub_division_id' => $subDivisionId,

            'stockable_id' => $stockableId,
            'stockable_type' => $stockableType,

            'quantity' => $quantity,
            'shipment_type' => $shipmentType,
        ]);
    }
    
    /**
     * Get cart count for current user/session
     */
    public static function getCount()
    {
        return self::forCurrent()->sum('quantity');
    }
    
    /**
     * Get cart total for current user/session
     */
    public static function getTotal()
    {
        return self::forCurrent()->withProduct()->get()->sum('total');
    }
    
    /**
     * Get cooking gas items only
     */
    public static function getCookingGasItems()
    {
        return self::forCurrent()->cookingGas()->withProduct()->get();
    }
    
    /**
     * Get cart grouped by subdivision
     */
    public static function getGroupedBySubdivision()
    {
        return self::forCurrent()
            ->withProduct()
            ->get()
            ->groupBy('subdivision_code');
    }
    
    /**
     * Get cart grouped by shipment type
     */
    public static function getGroupedByShipment()
    {
        return self::forCurrent()
            ->withProduct()
            ->get()
            ->groupBy('shipment_type');
    }
}