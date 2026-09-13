<?php

namespace App\Services\Notification\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\Customer\Order;


class CustomerSMSNotificationService
{
    public function __construct(
        protected TalkSasaService $sms
    ) {}

    /**
     * Notify customer that the order has been placed.
     */
    public function orderPlaced(Order $order): void
    {
        $customer = $order->customer;

        if (! $customer || empty($customer->phone_number)) {
            return;
        }

        $message = sprintf(
            "Hello %s,\nYour order (OrderNo: %s) has been placed successfully. We will notify you once it has been delivered.\n\nThank you for shopping with us.",
            $customer->name,
            $order->order_id
        );

        $this->sms->send(
            $customer->phone_number,
            $message
        );
    }
}