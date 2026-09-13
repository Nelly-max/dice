<?php

namespace App\Services\OrderPayment;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Customer\CheckoutInvoice;
use App\Models\Customer\CheckoutItem;
use App\Models\Customer\MpesaTransactionOrder;
use App\Models\Customer\Order;
use App\Models\Customer\OrderBusiness;
use App\Models\Customer\OrderItem;

use App\Services\Notification\SMS\CustomerSMSNotificationService;


class PaymentProcessingService
{
    public function __construct(
    protected CustomerSMSNotificationService $notificationService
    ) {}

    /**
     * Process a successful M-Pesa payment.
     */
    public function processSuccessfulPayment(
        MpesaTransactionOrder $transaction,
        float $amount
    ): void
    {
        $invoice = $transaction->checkoutInvoice;

        if (! $invoice) {
            return;
        }

        /**
         * Increase the amount paid.
         */
        $invoice->increment('amount_paid', $amount);

        $invoice->refresh();

        /**
         * Fully paid.
         */
        if ($invoice->amount_paid >= $invoice->total_amount) {

            $invoice->update([
                'status' => 'order-placed',
            ]);

            $invoice->refresh();

            $this->createOrder($invoice);

            return;
        }

        /**
         * Partial payment.
         * Do not create the order until it is fully paid.
         */
        if ($invoice->amount_paid > 0) {

            $invoice->update([
                'status' => 'partial',
            ]);
        }
    }

    /**
     * Process Cash on Delivery.
     */
    public function processCashOnDelivery(
        CheckoutInvoice $invoice
    ): void
    {
        $invoice->update([
            'status' => 'order-placed',
        ]);

        $invoice->refresh();

        $this->createOrder($invoice);
    }

    /**
     * Create or update the order.
     */
    private function createOrder(CheckoutInvoice $invoice): void
    {
        $order = DB::connection('customer')->transaction(function () use ($invoice) {

            /**
             * Determine the order payment status.
             */
            if ($invoice->payment_method === 'cash-on-delivery') {

                $paymentStatus = 'cod';

            } elseif ($invoice->amount_paid > $invoice->total_amount) {

                $paymentStatus = 'overpaid';

            } elseif ($invoice->amount_paid == $invoice->total_amount) {

                $paymentStatus = 'fully-paid';

            } elseif ($invoice->amount_paid > 0) {

                $paymentStatus = 'partial-payment';

            } else {

                $paymentStatus = 'partial-payment';
            }

            /**
             * Lock existing order if it exists.
             */
            $order = Order::where('checkout_invoice_id', $invoice->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {

                $order = new Order();

                $order->checkout_invoice_id = $invoice->id;

                $order->uuid = (string) Str::ulid();

                $order->order_id = $this->generateOrderId($invoice);

                $order->placed_at = now();
            }

            $order->customer_id     = $invoice->customer_id;
            $order->payment_account = $invoice->payment_account;
            $order->payment_method  = $invoice->payment_method;

            $order->subtotal        = $invoice->subtotal;
            $order->delivery_fee    = $invoice->delivery_fee;
            $order->discount        = $invoice->discount ?? 0;
            $order->total_amount    = $invoice->total_amount;

            $order->payment_status  = $paymentStatus;
            $order->status          = 'placed';
            $order->delivery_note   = $invoice->delivery_note;

            $order->save();

            /*
            |--------------------------------------------------------------------------
            | Create Order Businesses & Order Items
            |--------------------------------------------------------------------------
            */

            $checkoutItems = CheckoutItem::where(
                'checkout_invoice_id',
                $invoice->id
            )->get();

            $businessGroups = $checkoutItems->groupBy('business_account');

            foreach ($businessGroups as $businessAccount => $items) {

                $firstItem = $items->first();

                $orderBusiness = OrderBusiness::updateOrCreate(

                    [
                        'order_id' => $order->id,
                        'business_account' => $businessAccount,
                    ],

                    [
                        'sub_division_id' => $firstItem->sub_division_id,
                        'status'          => 'pending',
                    ]
                );

                foreach ($items as $item) {

                    $modelClass = config('stockables.' . $item->stockable_type);

                    if (! $modelClass) {
                        throw new \Exception(
                            "Unsupported stockable type: {$item->stockable_type}"
                        );
                    }

                    $stockable = $modelClass::findOrFail($item->stockable_id);

                    $unitPrice = $stockable->checkout_price;

                    $subtotal = $unitPrice * $item->quantity;

                    OrderItem::updateOrCreate(

                        [
                            'order_id' => $order->id,
                            'checkout_item_id' => $item->id,
                        ],

                        [
                            'order_business_id' => $orderBusiness->id,

                            'checkout_item_id' => $item->id,

                            'cart_item_id' => $item->cart_item_id,

                            'stockable_id' => $item->stockable_id,

                            'stockable_type' => $item->stockable_type,

                            'business_account' => $item->business_account,

                            'quantity' => $item->quantity,

                            'unit_price' => $unitPrice,

                            'subtotal' => $subtotal,
                        ]
                    );
                }
            }

            return $order;
        });

        /*
        |--------------------------------------------------------------------------
        | Notify Customer on Sms
        |--------------------------------------------------------------------------
        */

        // try {

        // $this->notificationService->orderPlaced($order);

        // } catch (\Throwable $e) {

        //     Log::error('Failed to send order placed SMS.', [
        //         'order_id' => $order->order_id,
        //         'error'    => $e->getMessage(),
        //     ]);
        // }
    }

    /**
     * Generate the business order ID.
     *
     * Format:
     * YYYYMMDD + Daily Sequence + County Sequence
     * Example:
     * 20260718000110
     */
    private function generateOrderId(CheckoutInvoice $invoice): string
    {
        $date = now()->format('Ymd');

        /**
         * Lock today's last order.
         */
        $lastOrder = Order::whereDate('created_at', today())
            ->whereNotNull('order_id')
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $dailySequence = 1;

        if ($lastOrder) {

            $dailySequence = ((int) substr($lastOrder->order_id, 8, 4)) + 1;
        }

        return sprintf(
            '%s%04d%02d',
            $date,
            $dailySequence,
            $invoice->county_sequence
        );
    }
}