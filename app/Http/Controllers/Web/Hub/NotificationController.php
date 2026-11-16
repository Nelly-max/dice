<?php

namespace App\Http\Controllers\Web\Hub;

use App\Http\Controllers\Controller;
use App\Models\Customer\CustomerNotification;

/**
 * Hub notifications (the bell, partials/Hub/notifications): open one (marks it read and goes
 * where it points), or mark them all read when the panel is opened.
 */
class NotificationController extends Controller
{
    public function open(int $notification)
    {
        $n = CustomerNotification::where('customer_id', auth('customer')->id())->findOrFail($notification);
        if (!$n->read_at) {
            $n->forceFill(['read_at' => now()])->save();
        }

        $url = $n->url;
        // only our own pages
        if (!$url || (parse_url($url, PHP_URL_HOST) && parse_url($url, PHP_URL_HOST) !== request()->getHost())) {
            $url = route('hub.account.index');
        }

        return redirect()->to($url);
    }

    public function readAll()
    {
        try {
            CustomerNotification::where('customer_id', auth('customer')->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['success' => true]);
    }
}
