<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

use App\Models\SubDivision; 

class CheckoutItem extends Model
{
    /**
     * The database connection that should be used by the model.
     * Maps explicitly to your 'customer' database connection.
     *
     * @var string
     */
    protected $connection = 'customer';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'checkout_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'checkout_invoice_id',
        'cart_item_id',
        'stockable_id',
        'stockable_type',
        'business_account',
        'sub_division_id',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'checkout_invoice_id' => 'integer',
        'cart_item_id'        => 'integer',
        'stockable_id'        => 'integer',
        'sub_division_id'     => 'integer',
        'quantity'            => 'integer',
    ];

    /**
     * Get the parent invoice that owns this checkout item.
     */
    public function checkoutInvoice(): BelongsTo
    {
        return $this->belongsTo(CheckoutInvoice::class, 'checkout_invoice_id');
    }

    /**
     * Get the original cart item reference.
     */
    public function cartItem(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Get the underlying polymorphic stockable model.
     * (e.g., App\Models\Product or App\Models\Service)
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the sub-division mapping across your separate hub database.
     * Ensure your SubDivision model uses the 'hub' connection or defines its table prefix.
     */
    public function subDivision(): BelongsTo
    {
        return $this->belongsTo(SubDivision::class, 'sub_division_id');
    }
}
