<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    /**
     * The database connection that should be used by the model.
     * Explicitly bound to your 'customer' database connection.
     *
     * @var string
     */
    protected $connection = 'customer';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'order_business_id',
        'checkout_item_id',
        'cart_item_id',
        'stockable_id',
        'stockable_type',
        'item_code',
        'business_account',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_id'          => 'integer',
        'order_business_id' => 'integer',
        'checkout_item_id'  => 'integer',
        'cart_item_id'      => 'integer',
        'stockable_id'      => 'integer',
        'quantity'          => 'integer',
        'unit_price'        => 'decimal:2',
        'subtotal'          => 'decimal:2',
    ];

    /**
     * Get the master order that this item belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the business fulfillment grouping instance holding this line item.
     */
    public function orderBusiness(): BelongsTo
    {
        return $this->belongsTo(OrderBusiness::class, 'order_business_id');
    }

    /**
     * Get the historical parent checkout snapshot reference record.
     */
    public function checkoutItem(): BelongsTo
    {
        return $this->belongsTo(CheckoutItem::class, 'checkout_item_id');
    }

    /**
     * Get the historical abandoned/converted cart item record.
     */
    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Get the underlying polymorphic purchased item.
     * (e.g., App\Models\Product, App\Models\Service, etc.)
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_business_id');
    }
}
