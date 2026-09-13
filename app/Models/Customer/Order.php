<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'customer';
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    protected $fillable = [

        'checkout_invoice_id',

        'customer_id',

        'payment_account',

        'payment_method',

        'subtotal',

        'delivery_fee',

        'discount',

        'total_amount',

        'payment_status',

        'status',

        'delivery_note',

        'placed_at',

        'completed_at',

        'cancelled_at',

    ];

    protected $casts = [

        'subtotal' => 'decimal:2',

        'delivery_fee' => 'decimal:2',

        'discount' => 'decimal:2',

        'total_amount' => 'decimal:2',

        'placed_at' => 'datetime',

        'completed_at' => 'datetime',

        'cancelled_at' => 'datetime',

    ];

    /**
     * Invoice that generated this order.
     */
    public function checkoutInvoice(): BelongsTo
    {
        return $this->belongsTo(
            CheckoutInvoice::class,
            'checkout_invoice_id'
        );
    }

    /**
     * Customer who placed the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            CustomerAccount::class,
            'customer_id'
        );
    }

    public function orderPlaced(Order $order)
    {
        return view('orders.placed', compact('order'));
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}