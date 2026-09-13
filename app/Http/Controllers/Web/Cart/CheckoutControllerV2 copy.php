<?php

namespace App\Http\Controllers\Web\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use App\Models\Customer\Cart;
use App\Models\Customer\CheckoutInvoice;
use App\Models\Customer\CheckoutItem;
use App\Models\Customer\DailyCountySequence;

class CheckoutControllerV2 extends Controller
{
    public function prepare(Request $request)
    {
        // Validate checkout data

        // Store checkout data in session

        session([
            'checkout' => $request->all()
        ]);

        return response()->json([
            'success' => true,
            'redirect' => route('payment.mpesa')
        ]);
    }
    
    public function checkoutInvoice(Request $request)
    {
        $request->validate([
            'payment_method' => ['required'],
            'delivery_fee'   => ['nullable', 'numeric', 'min:0'],
        ]);


        $checkout = session('checkout');

        if (
            !$checkout ||
            empty($checkout['shipment']) ||
            !is_array($checkout['shipment'])
        ) {
            return redirect()
                ->route('cart.checkout')
                ->with('error', 'Checkout session expired.');
        }


        $customer = auth('customer')->user();

        if (!$customer) {
            return redirect()
                ->route('customer.login');
        }



        /*
        |--------------------------------------------------------------------------
        | Selected Cart Items
        |--------------------------------------------------------------------------
        */

        $cartIds = collect($checkout['shipment'])
            ->pluck('items')
            ->flatten(1)
            ->pluck('cart_id')
            ->unique()
            ->values();


        $cartItems = Cart::forCurrent()
            ->with('stockable')
            ->whereIn('id', $cartIds)
            ->get();


        if ($cartItems->isEmpty()) {

            return redirect()
                ->route('cart.index')
                ->with('error', 'No selected cart items found.');

        }



        /*
        |--------------------------------------------------------------------------
        | First Business Receiving this Shipment
        |--------------------------------------------------------------------------
        */

        $firstBusinessData = collect($checkout['shipment'])
            ->first();


        if (!$firstBusinessData) {

            return redirect()
                ->route('cart.index')
                ->with('error', 'Unable to determine receiving business.');

        }


        $dbConnection = $firstBusinessData['db_connection'] ?? null;

        $businessAccount = $firstBusinessData['business_account'] ?? null;


        if (!$dbConnection || !$businessAccount) {

            return redirect()
                ->route('cart.index')
                ->with('error', 'Invalid business information.');

        }


        $business = DB::connection($dbConnection)
            ->table('business')
            ->where('account', $businessAccount)
            ->first();


        if (!$business) {

            return redirect()
                ->route('cart.index')
                ->with('error', 'Receiving business not found.');

        }


        if (!$business->county_id) {

            return redirect()
                ->route('cart.index')
                ->with('error', 'Business county not configured.');

        }



        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */

        $subtotal = $cartItems->sum(function ($item) {

            return ($item->price ?? 0) * $item->quantity;

        });


        $discount = $cartItems->sum(function ($item) {

            return ($item->stockable->discount ?? 0)
                * $item->quantity;

        });


        /*
        |--------------------------------------------------------------------------
        | Delivery Fee From Checkout
        |--------------------------------------------------------------------------
        */

        $delivery = (float) ($request->delivery_fee ?? 0);


        $total = $subtotal + $delivery - $discount;



        /*
        |--------------------------------------------------------------------------
        | Generate Invoice Number & Payment Account
        |--------------------------------------------------------------------------
        */

        $invoiceData = $this->generateInvoiceNumber();


        $paymentData = $this->generatePaymentAccount(
            $business->county_id
        );



        /*
        |--------------------------------------------------------------------------
        | Create Checkout Invoice
        |--------------------------------------------------------------------------
        */

        $invoice = CheckoutInvoice::create([

            'uuid' => Str::ulid(),

            'invoice_number' => $invoiceData['invoice_number'],
            'sequence_number' => $invoiceData['sequence_number'],

            'payment_account' => $paymentData['payment_account'],

            'customer_id' => $customer->id,

            'county_id' => $paymentData['county_id'],
            'county_sequence' => $paymentData['county_sequence'],

            'payment_method' => $request->payment_method,

            'subtotal' => $subtotal,
            'delivery_fee' => $delivery,
            'discount' => $discount,
            'total_amount' => $total,

            'amount_paid' => 0,
            'status' => 'pending',

        ]);

        /*

        |--------------------------------------------------------------------------
        | Snapshot Checkout Items
        |--------------------------------------------------------------------------
        */

        foreach ($cartItems as $item) {

            CheckoutItem::create([

                'checkout_invoice_id' => $invoice->id,

                'cart_item_id'        => $item->id,

                'stockable_id'        => $item->stockable_id,

                'stockable_type'      => $item->stockable_type,

                'business_account'    => $item->business_account,

                'sub_division_id'     => $item->sub_division_id,

                'quantity'            => $item->quantity,

            ]);

        }



        /*
        |--------------------------------------------------------------------------
        | Store Invoice In Session
        |--------------------------------------------------------------------------
        */

        session([
            'checkout_invoice_id'     => $invoice->id,
            'checkout_invoice_number' => $invoice->invoice_number,
            'payment_account'         => $invoice->payment_account,
        ]);



        /*
        |--------------------------------------------------------------------------
        | Redirect To Payment
        |--------------------------------------------------------------------------
        */

        switch ($invoice->payment_method) {

            case 'mpesa':

                return redirect()->route('payment.mpesa', [
                    'invoice' => $invoice->uuid,
                ]);


            default:

                return back()
                    ->with('error', 'Unsupported payment method.');

        }
    }

    private function generateInvoiceNumber()
    {
        $today = now()->toDateString();

        $lastSequence = CheckoutInvoice::whereDate('created_at', $today)
            ->max('sequence_number');

        $sequence = ($lastSequence ?? 0) + 1;

        return [
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . $sequence,
            'sequence_number' => $sequence,
        ];
    }

    private function generatePaymentAccount($countyId)
    {
        return DB::transaction(function () use ($countyId) {

            $today = now()->toDateString();

            /*
            |--------------------------------------------------------------------------
            | Get or create today's county code
            |--------------------------------------------------------------------------
            */
            $dailyCounty = DailyCountySequence::where('sequence_date', $today)
                ->where('county_id', $countyId)
                ->lockForUpdate()
                ->first();

            if (!$dailyCounty) {

                $lastCode = DailyCountySequence::where('sequence_date', $today)
                    ->lockForUpdate()
                    ->max('daily_code');

                $dailyCounty = DailyCountySequence::create([
                    'sequence_date' => $today,
                    'county_id'     => $countyId,
                    'daily_code'    => ($lastCode ?? 9) + 1, // first code becomes 10
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | County invoice sequence for today
            |--------------------------------------------------------------------------
            */
            $countySequence = CheckoutInvoice::whereDate('created_at', $today)
                ->where('county_id', $countyId)
                ->lockForUpdate()
                ->count() + 1;

            /*
            |--------------------------------------------------------------------------
            | Payment Account
            |--------------------------------------------------------------------------
            | Format:
            | [CountySequence][Day][Month][DailyCountyCode]
            | Example:
            | 27 15 07 10
            | => 27150710
            */
            $paymentAccount =
                $countySequence .
                now()->format('dm') .
                $dailyCounty->daily_code;

            return [
                'payment_account'          => $paymentAccount,
                'county_sequence'          => $countySequence,
                'county_id'                => $countyId,
            ];
        });
    }


}
