{{--
    Floating "back to the map" button for a rider with a delivery in progress (shown on
    every page: layouts/hub and partials/rider-request). Opens /hub/navigate/{delivery}.
--}}
@php
    $customer = Auth::guard('customer')->user();

    $rider = $customer
        ? \App\Models\Hub\Rider::where('customer_id', $customer->id)->first()
        : null;

    $activeDelivery = $rider
        ? \App\Models\Customer\Delivery::where('rider_account', $rider->rider_account)
            ->whereIn('status', [
                'rider_assigned',
                'rider_arrived_shop',
                'picked_up',
                'on_transit',
                'delivering',
                'in_progress',
            ])
            ->latest('id')
            ->first(['id', 'status', 'pickup_name', 'customer_name'])
        : null;

    $hasActiveDelivery = (bool) $activeDelivery;
    $toCustomer = $activeDelivery && in_array($activeDelivery->status, ['picked_up', 'on_transit', 'delivering'], true);
@endphp

<div class="toast {{ $hasActiveDelivery ? 'active' : '' }}" id="activeDeliveryToast">
    <div class="bottom-toast no-sidebar">
        <div class="bottom-toast-data">
            <div class="btns">
                <button
                    class="btn btn-open dm-fab"
                    id="openNavigation"
                    type="button"
                    data-delivery-id="{{ $activeDelivery->id ?? '' }}"
                    aria-label="Open the delivery map"
                    title="Open the delivery map"
                >
                    <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
                    @if ($activeDelivery)
                        <span class="dm-fab-label">{{ $toCustomer ? 'To ' . ($activeDelivery->customer_name ?: 'customer') : 'Pickup: ' . ($activeDelivery->pickup_name ?: 'shop') }}</span>
                    @endif
                </button>
            </div>
        </div>
    </div>
</div>
