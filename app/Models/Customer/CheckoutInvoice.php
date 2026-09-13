<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;
use App\Models\PhysicalCounty;

class CheckoutInvoice extends Model
{
    protected $connection = 'customer';

    protected $table = 'checkout_invoices';


    protected $fillable = [

        // Invoice
        'uuid',
        'invoice_number',
        'sequence_number',


        // Payment Account
        'payment_account',

        'payment_method',


        // Customer
        'customer_id',


        /*
        |--------------------------------------------------------------------------
        | Customer Snapshot
        |--------------------------------------------------------------------------
        */

        'name',

        'email',

        'phone_number',

        'alternative_phone_number',


        /*
        |--------------------------------------------------------------------------
        | Delivery Snapshot
        |--------------------------------------------------------------------------
        */

        'delivery_location',

        'latitude',

        'longitude',

        'delivery_note',



        // County
        'county_id',

        'daily_county_sequence_id',

        'county_sequence',



        // Amounts
        'subtotal',

        'delivery_fee',

        'discount',

        'total_amount',

        'amount_paid',



        // Status
        'status',



        // Order
        'order_id',



        // Payment Date
        'paid_at',

    ];


    protected $casts = [

        'subtotal'      => 'decimal:2',

        'delivery_fee'  => 'decimal:2',

        'discount'      => 'decimal:2',

        'total_amount'  => 'decimal:2',

        'amount_paid'   => 'decimal:2',


        'latitude'  => 'decimal:7',

        'longitude' => 'decimal:7',


        'paid_at' => 'datetime',

    ];



    /**
     * Customer who initiated checkout.
     */
    public function customer()
    {
        return $this->belongsTo(
            CustomerAccount::class,
            'customer_id'
        );
    }



    /**
     * County (Hub Database)
     */
    public function county()
    {
        return $this->belongsTo(
            PhysicalCounty::class,
            'county_id'
        );
    }



    /**
     * Daily county assignment.
     */
    public function dailyCountySequence()
    {
        return $this->belongsTo(
            DailyCountySequence::class,
            'daily_county_sequence_id'
        );
    }

}