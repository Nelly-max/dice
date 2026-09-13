<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
class ShipmentService
{
    public function buildShipment($cartItems)
    {
        // =====================================================
        // SHIPMENT TYPES
        // =====================================================
        $shipmentTypes = DB::table('shipment_types')
            ->get()
            ->keyBy('id');

        $shipmentInfo = [
            'quick' => [
                'icon' => 'ri-e-bike-2-line',
                'label' => 'Quick in 10 - 50 Mins'
            ],
            'standard' => [
                'icon' => 'ri-truck-line',
                'label' => 'Standard delivery'
            ],
            'specific_shop' => [
                'icon' => 'ri-riding-line',
                'label' => 'Order to specific shop'
            ]
        ];

        // =====================================================
        // LOAD SUBDIVISION CONNECTIONS
        // =====================================================
        $connections = DB::table('sub_divisions')
            ->pluck('db_connection', 'id');

        $businessCache = [];

        // =====================================================
        // GROUP BY SHIPMENT TYPE
        // =====================================================
        $groupedByType = $cartItems->groupBy('shipment_type_id');

        $shipments = [];

        foreach ($groupedByType as $typeId => $items) {

            $typeRule = $shipmentTypes->get($typeId);

            $typeName = strtolower($typeRule->name ?? 'standard');

            $minAmount = $typeRule->min_amount ?? 0;

            $timeRange = [
                'from' => $typeRule->time1 ?? null,
                'to'   => $typeRule->time2 ?? null,
            ];

            $groupItems = collect();
            $singleItems = collect();

            foreach ($items as $item) {

                $consignment = strtolower(
                    $item->subdivision?->shipment?->consignment ?? 'group'
                );

                if ($consignment === 'single') {
                    $singleItems->push($item);
                } else {
                    $groupItems->push($item);
                }
            }

            if ($groupItems->isNotEmpty()) {

                $shipments["group_{$typeId}"] = [
                    'shipment_type_id' => $typeId,
                    'shipment_type' => $typeName,
                    'mode' => 'group',
                    'subdivision' => null,
                    'min_amount' => $minAmount,
                    'time_range' => $timeRange,
                    'items' => $groupItems,
                ];
            }

            foreach ($singleItems as $item) {

                $shipments["single_{$typeId}_{$item->id}"] = [
                    'shipment_type_id' => $typeId,
                    'shipment_type' => $typeName,
                    'mode' => 'single',
                    'subdivision' => $item->subdivision,
                    'min_amount' => $minAmount,
                    'time_range' => $timeRange,
                    'items' => collect([$item]),
                ];
            }
        }

        // =====================================================
        // FORMAT SHIPMENTS
        // =====================================================
        $formattedShipments = [];
        $shipmentNumber = 1;

        foreach ($shipments as $shipment) {

            $items = $shipment['items'];

            $subtotal = $items->sum(function ($item) {
                return ($item->price ?? 0) * $item->quantity;
            });

            $typeName = $shipment['shipment_type'];

            foreach ($items as $item) {

                $dbConn = $connections[$item->sub_division_id] ?? 'mysql';

                if (!isset($businessCache[$item->sub_division_id])) {

                    $businessCache[$item->sub_division_id] = DB::connection($dbConn)
                        ->table('business')
                        ->where('subdivision_id', $item->sub_division_id)
                        ->select('name', 'latitude', 'longitude')
                        ->first();
                }

                $business = $businessCache[$item->sub_division_id];

                $item->explicit_db_connection = $dbConn;
                $item->explicit_biz_name = $business?->name;
                $item->explicit_latitude = $business?->latitude;
                $item->explicit_longitude = $business?->longitude;
            }

            $formattedShipments[] = [
                'number' => $shipmentNumber,
                'title' => 'Shipment ' . $shipmentNumber,

                'shipment_type' => $typeName,
                'shipment_type_id' => $shipment['shipment_type_id'],

                'info' => $shipmentInfo[$typeName] ?? [
                    'icon' => 'ri-package-line',
                    'label' => ucfirst($typeName)
                ],

                'mode' => $shipment['mode'],
                'subdivision' => $shipment['subdivision'],

                'min_amount' => $shipment['min_amount'],
                'time_range' => $shipment['time_range'],

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