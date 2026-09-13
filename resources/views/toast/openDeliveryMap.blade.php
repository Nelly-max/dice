@php
    $customer = Auth::guard('customer')->user();

    $rider = $customer
        ? \App\Models\Hub\Rider::where('customer_id', $customer->id)->first()
        : null;

    $hasActiveDelivery = $rider
        ? \App\Models\Customer\Delivery::where(
            'rider_account',
            $rider->rider_account
        )
        ->whereIn('status', [
            'rider_assigned',
            'picked_up',
            'delivering',
            'in_progress'
        ])
        ->exists()
        : false;
@endphp

<div class="toast {{ $hasActiveDelivery ? 'active' : '' }}" id="activeDeliveryToast">

    <div class="bottom-toast no-sidebar">

        <div class="bottom-toast-data">

            <div class="btns">

                <button
                    class="btn btn-open"
                    id="openNavigation"
                    type="button"
                    aria-label="Open navigation"
                    title="Open navigation"
                >
                    <i class="fa-solid fa-map-location-dot"></i>
                </button>

            </div>

        </div>

    </div>

</div>