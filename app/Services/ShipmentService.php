<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Customer\ShipmentType;

class ShipmentService
{
    public function buildShipment($cartItems)
    {
        /*
        |--------------------------------------------------------------------------
        | Load Shipment Types From Customer Database
        |--------------------------------------------------------------------------
        */

        $shipmentTypes = ShipmentType::all()->keyBy('id');

        $shipmentInfo = [
            'quick' => [
                'icon' => 'ri-e-bike-2-line',
                'label' => 'Quick in 10 - 50 Mins',
            ],
            'standard' => [
                'icon' => 'ri-truck-line',
                'label' => 'Standard delivery',
            ],
            'specific_shop' => [
                'icon' => 'ri-riding-line',
                'label' => 'Order to specific shop',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Load Subdivision Connections From HUB
        |--------------------------------------------------------------------------
        */

        $connections = DB::table('sub_divisions')
            ->pluck('db_connection', 'id');

        $businessCache = [];

        /*
        |--------------------------------------------------------------------------
        | Group Items By Seller (business_account)
        |--------------------------------------------------------------------------
        | Items from the same seller belong to the same shipment.
        */

        $groupedBySeller = $cartItems->groupBy('business_account');

        /*
        |--------------------------------------------------------------------------
        | Format Shipments
        |--------------------------------------------------------------------------
        */

        $formattedShipments = [];
        $shipmentNumber = 1;

        foreach ($groupedBySeller as $businessAccount => $items) {

            $subtotal = $items->sum(function ($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 0);
            });

            /*
            |--------------------------------------------------------------------------
            | Resolve Shipment Type From First Item
            |--------------------------------------------------------------------------
            */

            $firstItem = $items->first();

            $typeId = $firstItem->shipment_type_id;
            $typeRule = $shipmentTypes->get($typeId);
            $typeName = strtolower($typeRule->name ?? 'standard');

            $minAmount = $typeRule->min_amount ?? 0;

            $timeRange = [
                'from' => $typeRule->time1 ?? null,
                'to' => $typeRule->time2 ?? null,
            ];

            /*
            |--------------------------------------------------------------------------
            | Resolve Business Details For Each Item
            |--------------------------------------------------------------------------
            */

            $storeName = null;

            foreach ($items as $item) {
                $subdivisionId = $item->sub_division_id;

                $dbConn = $connections[$subdivisionId] ?? null;

                $cacheKey = $subdivisionId . '_' . $item->business_account;

                if (!isset($businessCache[$cacheKey])) {
                    $businessCache[$cacheKey] = null;

                    if ($dbConn) {
                        $businessCache[$cacheKey] = DB::connection($dbConn)
                            ->table('business')
                            ->where('account', $item->business_account)
                            ->select('name', 'latitude', 'longitude')
                            ->first();
                    }
                }

                $business = $businessCache[$cacheKey];

                $item->explicit_db_connection = $dbConn;
                $item->explicit_biz_name = $business?->name;
                $item->explicit_latitude = $business?->latitude;
                $item->explicit_longitude = $business?->longitude;

                if (!$storeName && $business?->name) {
                    $storeName = $business->name;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Use Business Name As Shipment Title
            |--------------------------------------------------------------------------
            */

            $title = $storeName ?? $businessAccount ?? 'Shipment ' . $shipmentNumber;

            $formattedShipments[] = [
                'number' => $shipmentNumber,
                'title' => $title,

                'business_account' => $businessAccount,

                'shipment_type' => $typeName,
                'shipment_type_id' => $typeId,

                'info' => $shipmentInfo[$typeName] ?? [
                    'icon' => 'ri-package-line',
                    'label' => ucfirst($typeName),
                ],

                'subdivision' => $firstItem->subdivision,
                'subdivision_id' => $firstItem->sub_division_id,

                'min_amount' => $minAmount,
                'time_range' => $timeRange,

                'items' => $items,

                'item_count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),

                'subtotal' => $subtotal,
            ];

            $shipmentNumber++;
        }

        return $formattedShipments;
    }
}
