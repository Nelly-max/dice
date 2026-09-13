<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $connection = 'customer';

    protected $table = 'sales';

    protected $fillable = [

        'reference',

        'sub_division_id',

        'subdivision_sale_id',

        'business_account',

        'customer_account',

        'shop_user_id',

        'customer_type',

        'purchase_channel',

        'status',

        'payment_status',

        'subtotal',

        'discount',

        'total',

    ];

    protected $casts = [

        'subtotal' => 'decimal:2',

        'discount' => 'decimal:2',

        'total' => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // public function items()
    // {
    //     return $this->hasMany(
    //         SaleItem::class,
    //         'sale_id'
    //     );
    // }

    // public function payments()
    // {
    //     return $this->hasMany(
    //         SalePayment::class,
    //         'sale_id'
    //     );
    // }
}