<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaTransactionOrder extends Model
{
    use HasFactory;

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
    protected $table = 'mpesa_transactions_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'checkout_invoice_id',
        'payment_account',
        'checkout_request_id',
        'merchant_request_id',
        'mpesa_receipt',
        'amount',
        'phone',
        'payment_method',
        'payment_status',
        'transaction_date',
        'result_code',
        'result_desc',
        'stk_request',
        'callback_payload',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'result_code' => 'integer',
        'stk_request' => 'array',
        'callback_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the invoice associated with the M-Pesa transaction.
     */
    public function checkoutInvoice(): BelongsTo
    {
        return $this->belongsTo(CheckoutInvoice::class, 'checkout_invoice_id');
    }

}
