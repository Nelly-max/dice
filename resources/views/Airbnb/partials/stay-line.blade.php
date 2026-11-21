{{-- The list layout of a stay card (results pages, "list" view). The whole row opens the stay. --}}
@php
    $size = $stay->unit_name
        ?? ($stay->bedrooms > 0 ? $stay->bedrooms . ' ' . \Illuminate\Support\Str::plural('bedroom', $stay->bedrooms) : 'Studio');
    $free = (int) ($stay->units_free ?? 1);
    $ranged = ($stay->price_max ?? 0) > $stay->price;
@endphp
<a href="{{ route('airbnb.stay', $stay->id) }}" @class(['gp-card', 'lf-line', 'is-full' => $free < 1])>
    <span class="gp-pic lf-photo" aria-hidden="true">
        <img src="{{ $stay->image }}" alt="" loading="lazy">
    </span>
    <span class="gp-body">
        <span class="lf-title"><strong>{{ $stay->title }}</strong>@if (!empty($stay->featured))<span class="gp-tag gp-tag--ok">Featured</span>@endif</span>
        <small class="lf-fact"><i class="fa-solid fa-bed" aria-hidden="true"></i>{{ \Illuminate\Support\Str::ucfirst($size) }}</small>
        @if (!empty($stay->area))
            <small class="lf-fact"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $stay->area }}</small>
        @endif
        <span class="gp-meta">
            <b>{{ $ranged ? 'From ' : '' }}KES {{ number_format($stay->price) }} / night</b>
            <em>{{ $free < 1 ? 'Fully booked' : $free . ' ' . \Illuminate\Support\Str::plural('unit', $free) . ' available' }}</em>
            @if (isset($stay->distance_km) && $stay->distance_km !== null)
                <em class="gp-dist"><i class="fa-solid fa-location-dot"></i>{{ $stay->distance_km < 1 ? round($stay->distance_km * 1000, -1) . ' m' : number_format($stay->distance_km, 1) . ' km' }}</em>
            @endif
        </span>
    </span>
</a>
