@extends('layouts.hub')

@section('content')
    <div class="hub-content no-sidebar">
        <div class="">
            <div class="table-top">
                <div class="top">
                    <h2 class="table_heading">My Deliveries</h2>

                    <div class="right">
                        <div class="search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="deliverySearch" placeholder="search">
                        </div>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Shop</th>
                            <th>Customer</th>
                            <th>Distance</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="deliveriesTable">
                        @forelse($deliveries as $delivery)
                            <tr
                                class="delivery-row"
                            >
                                <td>
                                    {{ $delivery->delivery_number ?? $delivery->id }}
                                </td>

                                <td>
                                    {{ $delivery->pickup_name ?? '-' }}
                                </td>

                                <td>
                                    @if($delivery->customer_name)
                                        → {{ $delivery->customer_name }}
                                    @endif
                                </td>

                                <td>
                                    @if($delivery->distance_km)
                                        {{ $delivery->distance_km ?? '-' }}KM
                                    @endif
                                </td>

                                <td>
                                    {{ optional($delivery->created_at)->format('d/m/Y') }}
                                </td>

                                <td>
                                    @php
                                        $status = strtolower($delivery->status ?? 'pending');

                                        $statusLabel = match ($status) {
                                            'on_transit' => 'On Transit',
                                            'on transit' => 'On Transit',
                                            'delivered' => 'Delivered',
                                            'completed' => 'Completed',
                                            'pending' => 'Pending',
                                            'assigned' => 'Assigned',
                                            'arrived_shop' => 'At Shop',
                                            'picked_up' => 'Picked Up',
                                            'cancelled' => 'Cancelled',
                                            default => ucwords(str_replace('_', ' ', $status)),
                                        };
                                    @endphp

                                    {{ $statusLabel }}
                                </td>

                                <td
                                    class="emptyBack"
                                    title="view"
                                >
                                    <i class="fa-solid fa-square-arrow-up-right"></i>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center;">
                                    No deliveries found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

