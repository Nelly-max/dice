<?php

namespace App\Services;

use App\Models\Customer\ShipmentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Groups cart items into shipments: one shipment per shop.
 *
 * Every item from the same shop (same subdivision + business account) goes in
 * the same shipment, because one rider collects them together. Items from a
 * different shop, or a different subdivision (Home Market, Liquor Hub, ...),
 * become a separate shipment.
 *
 * The keys used by the cart page and cart.js are kept:
 * number, title, shipment_type, shipment_type_id, info, mode, subdivision,
 * min_amount, time_range, items, item_count, total_quantity, subtotal.
 * Each item also gets explicit_db_connection / explicit_biz_name /
 * explicit_latitude / explicit_longitude for the checkout payload.
 */
class ShipmentService
{
    private const INFO = [
        'quick' => [
            'icon'  => 'fa-solid fa-motorcycle',
            'label' => 'Quick delivery',
            'eta'   => '10 – 50 mins',
        ],
        'standard' => [
            'icon'  => 'fa-solid fa-truck',
            'label' => 'Standard delivery',
            'eta'   => 'Same day',
        ],
        'specific_shop' => [
            'icon'  => 'fa-solid fa-store',
            'label' => 'Order to specific shop',
            'eta'   => 'Shop confirms the time',
        ],
    ];

    public function buildShipment(Collection $cartItems): array
    {
        if ($cartItems->isEmpty()) {
            return [];
        }

        $shipmentTypes = ShipmentType::all()->keyBy('id');

        // hub: subdivision id => db connection / name
        $subdivisions = DB::table('sub_divisions')
            ->select('id', 'name', 'db_connection')
            ->get()
            ->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | One group per shop
        |--------------------------------------------------------------------------
        */

        $groups = $cartItems->groupBy(function ($item) {
            return ($item->sub_division_id ?? 0) . '|' . ($item->business_account ?? 'unknown');
        });

        $shipments = [];
        $number = 1;

        foreach ($groups as $items) {

            $first = $items->first();
            $subdivision = $subdivisions->get($first->sub_division_id);
            $dbConnection = $subdivision->db_connection ?? null;

            $business = $this->business($dbConnection, $first->business_account);

            $shopName = $business->name
                ?? $first->business_name
                ?? 'Shop';

            foreach ($items as $item) {
                $item->explicit_db_connection = $dbConnection;
                $item->explicit_biz_name = $shopName;
                $item->explicit_latitude = $business->latitude ?? null;
                $item->explicit_longitude = $business->longitude ?? null;
            }

            // delivery type: the slowest type in the group decides (quick only if every item is quick)
            $typeId = $this->groupTypeId($items, $shipmentTypes);
            $typeRule = $shipmentTypes->get($typeId);
            $typeName = strtolower($typeRule->name ?? 'quick');

            $subtotal = $items->sum(fn ($item) => ($item->price ?? 0) * ($item->quantity ?? 0));
            $discount = $items->sum(fn ($item) => ($item->stockable->discount ?? 0) * ($item->quantity ?? 0));

            $shipments[] = [
                'number' => $number,
                'title' => $shopName,

                'shop_name' => $shopName,
                'shop_initial' => mb_strtoupper(mb_substr($shopName, 0, 1)),
                'business_account' => $first->business_account,
                'subdivision_id' => $first->sub_division_id,
                'subdivision_name' => $subdivision->name ?? null,
                'db_connection' => $dbConnection,

                'shipment_type' => $typeName,
                'shipment_type_id' => $typeId,
                'info' => self::INFO[$typeName] ?? [
                    'icon' => 'fa-solid fa-box',
                    'label' => ucfirst(str_replace('_', ' ', $typeName)),
                    'eta' => null,
                ],

                'mode' => 'shop',
                'subdivision' => $first->subdivision,

                'min_amount' => $typeRule->min_amount ?? 0,
                'time_range' => [
                    'from' => $typeRule->time1 ?? null,
                    'to' => $typeRule->time2 ?? null,
                ],

                'items' => $items->values(),
                'item_count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),

                'subtotal' => $subtotal,
                'discount' => $discount,
            ];

            $number++;
        }

        return $shipments;
    }

    /**
     * Shop details from the subdivision's own database (Home Market, Liquor Hub, ...).
     */
    private function business(?string $connection, ?string $account): ?object
    {
        static $cache = [];

        if (!$connection || !$account) {
            return null;
        }

        $key = $connection . '|' . $account;

        if (!array_key_exists($key, $cache)) {
            try {
                $cache[$key] = DB::connection($connection)
                    ->table('business')
                    ->where('account', $account)
                    ->select('name', 'latitude', 'longitude')
                    ->first();
            } catch (\Throwable $e) {
                report($e);
                $cache[$key] = null;
            }
        }

        return $cache[$key];
    }

    /**
     * The shipment type for a shop's items: if they differ, use the one with
     * the longest delivery time (time2), so the shipment never promises more
     * than the slowest item allows.
     */
    private function groupTypeId(Collection $items, Collection $shipmentTypes): ?int
    {
        $ids = $items->pluck('shipment_type_id')->filter()->unique()->values();

        if ($ids->count() <= 1) {
            return $ids->first();
        }

        return $ids->sortByDesc(fn ($id) => (float) ($shipmentTypes->get($id)->time2 ?? 0))->first();
    }
}
