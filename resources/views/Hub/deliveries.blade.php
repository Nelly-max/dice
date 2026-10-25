@extends('layouts.hub')

@section('content')
<div class="hub-content no-sidebar">
    <section class="table-top hub-table-card">

        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">My deliveries</h2>
                <span class="hub-count">{{ $deliveries->count() }}</span>
            </div>

            <div class="right hub-table-tools">
                <div class="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="deliverySearch" placeholder="Search shop, customer, status…" data-table-filter="deliveriesTable" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="hub-table-wrap">
            <table class="table hub-table" data-sortable>
                <thead>
                    <tr>
                        <th data-sort="text">Delivery</th>
                        <th data-sort="text">Shop</th>
                        <th data-sort="text">Customer</th>
                        <th data-sort="number" class="is-num">Distance</th>
                        <th data-sort="date">Date</th>
                        <th data-sort="text">Status</th>
                        <th class="is-action"><span class="sr-only">View</span></th>
                    </tr>
                </thead>

                <tbody id="deliveriesTable">
                    @forelse ($deliveries as $delivery)
                        @php
                            $status = strtolower($delivery->status ?? 'pending');

                            $statusLabel = match ($status) {
                                'on_transit', 'on transit' => 'On Transit',
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
                        <tr class="delivery-row">
                            <td data-label="Delivery"><strong>{{ $delivery->delivery_number ?? $delivery->id }}</strong></td>
                            <td data-label="Shop">{{ $delivery->pickup_name ?? '—' }}</td>
                            <td data-label="Customer">{{ $delivery->customer_name ?? '—' }}</td>
                            <td data-label="Distance" class="is-num" data-value="{{ (float) ($delivery->distance_km ?? 0) }}">
                                {{ $delivery->distance_km ? rtrim(rtrim(number_format((float) $delivery->distance_km, 1), '0'), '.') . ' km' : '—' }}
                            </td>
                            <td data-label="Date" data-value="{{ optional($delivery->created_at)->timestamp ?? 0 }}">
                                {{ optional($delivery->created_at)->format('d M Y') ?? '—' }}
                                @if ($delivery->created_at)<small>{{ $delivery->created_at->format('g:i A') }}</small>@endif
                            </td>
                            <td data-label="Status">
                                <span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($status) }}">{{ $statusLabel }}</span>
                            </td>
                            <td class="emptyBack is-action" title="View">
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="7">
                                <div class="hub-empty">
                                    <i class="fa-solid fa-motorcycle" aria-hidden="true"></i>
                                    <strong>No deliveries yet</strong>
                                    <span>Jobs you accept will show up here.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <tr class="hub-no-match" hidden>
                        <td colspan="7"><div class="hub-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><strong>No deliveries match your search</strong><span>Try a shop, customer or status.</span></div></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="hub-table-foot">
            <p class="hub-table-info" data-table-info="deliveriesTable">
                {{ $deliveries->count() }} {{ \Illuminate\Support\Str::plural('delivery', $deliveries->count()) }}
            </p>
        </div>
    </section>
</div>
@endsection
