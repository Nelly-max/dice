@extends('layouts.hub')

@section('content')
{{-- One of the rider's deliveries (DeliveryController@show). --}}
@php
    $status = strtolower($delivery->status ?? 'pending');
    $statusLabel = match ($status) {
        'on_transit', 'on transit' => 'On transit',
        'rider_assigned', 'assigned' => 'Assigned',
        'rider_arrived_shop', 'arrived_shop' => 'At the shop',
        'picked_up' => 'Picked up',
        default => ucwords(str_replace('_', ' ', $status)),
    };
    $active = in_array($status, ['rider_assigned', 'rider_arrived_shop', 'picked_up', 'on_transit', 'delivering', 'in_progress'], true);
    $fmt = fn ($t) => $t ? \Illuminate\Support\Carbon::parse($t)->format('d M Y, g:i A') : null;

    $steps = [
        ['Assigned to you', $delivery->assigned_at ?? null, 'fa-solid fa-user-check'],
        ['Arrived at the shop', $delivery->arrived_shop_at ?? null, 'fa-solid fa-store'],
        ['Picked up', $delivery->picked_up_at ?? null, 'fa-solid fa-box'],
        ['Delivered', $delivery->delivered_at ?? null, 'fa-solid fa-house-circle-check'],
        ['Completed', $delivery->completed_at ?? null, 'fa-solid fa-flag-checkered'],
    ];
    if ($delivery->cancelled_at) $steps[] = ['Cancelled', $delivery->cancelled_at, 'fa-solid fa-ban'];

    $mapLink = fn ($lat, $lng) => ($lat && $lng) ? 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng : null;
    $note = trim((string) ($order->delivery_note ?? '')) ?: null;
@endphp
<div class="hub-content no-sidebar hd-page">

    <a href="{{ route('hub.deliveries') }}" class="hd-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> My deliveries</a>

    <header class="hd-head">
        <span class="hd-head-icon"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i></span>
        <div>
            <h1>{{ $delivery->delivery_number ?? ('Delivery #' . $delivery->id) }}</h1>
            <p>{{ $fmt($delivery->created_at) ?? '—' }}</p>
        </div>
        <span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($status) }}">{{ $statusLabel }}</span>
    </header>

    @if ($active)
        <a href="{{ url('/hub/navigate/' . $delivery->id) }}" class="hd-cta"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Open the delivery map</a>
    @endif

    <section class="hd-stats" aria-label="Summary">
        <div class="hd-stat">
            <span class="hd-stat-icon"><i class="fa-solid fa-route" aria-hidden="true"></i></span>
            <div><small>Distance</small><strong>{{ $delivery->distance_km ? rtrim(rtrim(number_format((float) $delivery->distance_km, 1), '0'), '.') . ' km' : '—' }}</strong></div>
        </div>
        <div class="hd-stat">
            <span class="hd-stat-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
            <div><small>Estimated time</small><strong>{{ $delivery->eta_minutes ? '~' . $delivery->eta_minutes . ' min' : '—' }}</strong></div>
        </div>
        <div class="hd-stat is-good">
            <span class="hd-stat-icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
            <div><small>Delivery fee</small><strong>{{ isset($order->delivery_fee) ? 'KES ' . number_format((float) $order->delivery_fee, 2) : '—' }}</strong></div>
        </div>
    </section>

    <div class="hd-grid">
        <section class="hub-card">
            <h2 class="hub-card-title">Route</h2>
            <ol class="hd-route">
                <li>
                    <span class="hd-dot is-pickup" aria-hidden="true"></span>
                    <div>
                        <small>Pickup</small>
                        <strong>{{ $delivery->pickup_name ?? 'Shop' }}</strong>
                        <span class="hd-actions">
                            @if ($delivery->pickup_phone)<a href="tel:{{ $delivery->pickup_phone }}"><i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $delivery->pickup_phone }}</a>@endif
                            @if ($u = $mapLink($delivery->pickup_latitude, $delivery->pickup_longitude))<a href="{{ $u }}" target="_blank" rel="noopener"><i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i> Directions</a>@endif
                        </span>
                    </div>
                </li>
                <li>
                    <span class="hd-dot is-drop" aria-hidden="true"></span>
                    <div>
                        <small>Drop-off</small>
                        <strong>{{ $delivery->customer_name ?? 'Customer' }}</strong>
                        <span class="hd-actions">
                            @if ($active && $delivery->customer_phone)<a href="tel:{{ $delivery->customer_phone }}"><i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $delivery->customer_phone }}</a>@endif
                            @if ($active && ($u = $mapLink($delivery->dropoff_latitude, $delivery->dropoff_longitude)))<a href="{{ $u }}" target="_blank" rel="noopener"><i class="fa-solid fa-diamond-turn-right" aria-hidden="true"></i> Directions</a>@endif
                        </span>
                    </div>
                </li>
            </ol>

            <h3 class="hd-sub">Note from the customer</h3>
            <p @class(['hd-note', 'is-empty' => !$note])>{{ $note ?? 'No note for this delivery.' }}</p>
        </section>

        <section class="hub-card">
            <h2 class="hub-card-title">Timeline</h2>
            <ol class="hd-timeline">
                @foreach ($steps as [$label, $time, $icon])
                    <li @class(['is-done' => $time, 'is-cancel' => $label === 'Cancelled'])>
                        <span class="hd-tl-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                        <div><strong>{{ $label }}</strong><small>{{ $fmt($time) ?? 'Not yet' }}</small></div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</div>
@endsection
