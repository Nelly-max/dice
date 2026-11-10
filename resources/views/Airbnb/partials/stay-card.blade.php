{{-- One card per unit type of a property (StayController::stays), Home Market card shape, wide photo.
     Only the photo links to the stay. --}}
@php
    $types = config('airbnb.types', []);
    $typeLabel = $types[$stay->type][0] ?? 'Stay';
    $typeIcon = $types[$stay->type][1] ?? 'fa-solid fa-bed';
    $size = $stay->unit_name
        ?? ($stay->bedrooms > 0 ? $stay->bedrooms . ' ' . \Illuminate\Support\Str::plural('bedroom', $stay->bedrooms) : 'Studio');
    $total = (int) ($stay->units_total ?? 1);
    $free = (int) ($stay->units_free ?? 1);
    $guests = (int) ($stay->guests ?? 0);
    $ranged = ($stay->price_max ?? 0) > $stay->price;
@endphp
<article class="item-container card-data hc-item ab-item">
    <a href="{{ route('airbnb.stay', $stay->id) }}" class="thumb" aria-label="{{ $stay->title }}">
        <img src="{{ $stay->image }}" alt="" loading="lazy">
        @if (!empty($stay->superhost))
            <span class="badge hc-badge"><i class="fa-solid fa-award" aria-hidden="true"></i> Superhost</span>
        @elseif (!empty($stay->featured))
            <span class="badge hc-badge"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured</span>
        @endif
        @if (isset($stay->distance_km) && $stay->distance_km !== null)
            <span class="hc-dist">{{ $stay->distance_km < 1 ? round($stay->distance_km * 1000, -1) . ' m' : number_format($stay->distance_km, 1) . ' km' }}</span>
        @endif
    </a>

    <div class="item-body">
        <div class="buy">
            <span class="prices">
                <strong class="now">@if ($ranged)<small>from</small> @endif<small>KES</small> {{ number_format($stay->price) }} <small>/ night</small></strong>
            </span>
            @if (!empty($stay->rating))
                <span class="ab-rating" aria-label="Rated {{ $stay->rating }} out of 5"><i class="fa-solid fa-star" aria-hidden="true"></i> {{ number_format($stay->rating, 1) }}</span>
            @endif
        </div>

        <span class="size"><i class="{{ $typeIcon }}" aria-hidden="true"></i> {{ $size }}@if ($guests > 0) · {{ $guests }} {{ \Illuminate\Support\Str::plural('guest', $guests) }}@endif</span>

        <p class="name">{{ $stay->title }}</p>

        <span @class(['hc-avail', 'is-full' => $free < 1])>{{ $free < 1 ? 'Fully booked' : $free . ' ' . \Illuminate\Support\Str::plural('unit', $free) . ' available' }}</span>

        <span class="hc-loc"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $stay->area }}</span>
    </div>
</article>
