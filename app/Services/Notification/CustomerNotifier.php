<?php

namespace App\Services\Notification;

use App\Models\Customer\CustomerNotification;

/**
 * Puts a message in a customer's Hub notifications (the bell).
 * The admin hub (hub-claude) writes the same table directly when it comments on, approves or
 * rejects a rider application.
 */
class CustomerNotifier
{
    public static function send(int $customerId, string $type, string $title, ?string $body = null, ?string $url = null, ?string $icon = null): ?CustomerNotification
    {
        try {
            return CustomerNotification::create([
                'customer_id' => $customerId,
                'type' => $type,
                'title' => mb_substr($title, 0, 160),
                'body' => $body,
                'url' => $url,
                'icon' => $icon,
            ]);
        } catch (\Throwable $e) {
            report($e);            // the table may not be migrated yet: never break the page
            return null;
        }
    }

    /** Unread count for the bell (0 when the table is missing). */
    public static function unread(int $customerId): int
    {
        try {
            return CustomerNotification::where('customer_id', $customerId)->unread()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
