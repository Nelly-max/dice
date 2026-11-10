{{--
    Delivery requests on every page (not only the Hub): for a signed-in rider, the request
    card (toast.toast), the floating map button while a delivery is in progress
    (toast.openDeliveryMap) and resources/js/rider-request.js, which checks for a request, plays
    the request sound, counts down and accepts. Hub and map pages set $skipRiderRequest
    (riderLocation.js / deliveryMap.js already handle requests there).
--}}
@php
    $rqCustomer = empty($skipRiderRequest) ? auth('customer')->user() : null;
    $rqIsRider = $rqCustomer
        ? \Illuminate\Support\Facades\Cache::remember('is_rider.' . $rqCustomer->id, 600, function () use ($rqCustomer) {
            try {
                return \App\Models\Hub\Rider::where('customer_id', $rqCustomer->id)->exists();
            } catch (\Throwable $e) {
                return false;
            }
        })
        : false;
@endphp
@if ($rqIsRider)
    <div data-rider-request>
        @include('toast.toast')
        @include('toast.openDeliveryMap')
    </div>
    @vite('resources/js/rider-request.js')
@endif
