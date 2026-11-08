{{-- One card per unit type of a property (PropertyController@index), same card as Home Market's
     (HomeMarket/products/items) with a wider photo. Only the photo links to the listing, which opens
     on this unit type (?unit=). $property comes from PropertyController@index;
     $property->distance_km is set by HomeCity/home when the visitor has a location. --}}
@php
    $url = route('homecity.listing.view', array_filter([$property->listing_id, 'unit' => $property->group ?? null]));
    $total = (int) ($property->units_total ?? 0);
    $free = (int) ($property->units_vacant ?? 0);
    $selling = ($property->kind ?? '') === 'sale' || ($property->type_label ?? '') === 'For Sale';
    $suffix = trim($property->price_suffix ?? '');
    $ranged = $property->min_price != $property->max_price && $property->max_price > 0;
@endphp
<article class="item-container card-data hc-item">
    <a href="{{ $url }}" class="thumb" aria-label="{{ $property->display_title }}">
        <img src="{{ $property->display_image }}" alt="" loading="lazy">
        <span class="badge hc-badge">{{ $property->type_label }}</span>
        @isset($property->distance_km)
            <span class="hc-dist">{{ $property->distance_km < 1 ? round($property->distance_km * 1000, -1) . ' m' : number_format($property->distance_km, 1) . ' km' }}</span>
        @endisset
    </a>

    <div class="item-body">
        <div class="buy">
            <span class="prices">
                <strong class="now">@if ($ranged)<small>from</small> @endif<small>KES</small> {{ number_format($property->min_price) }}@if ($suffix !== '') <small>{{ $suffix }}</small>@endif</strong>
            </span>
        </div>

        <span class="size"><i class="{{ $property->icon ?? 'fa-solid fa-building' }}" aria-hidden="true"></i> {{ $property->unit_type }}</span>

        <p class="name">{{ $property->display_title }}</p>

        @if ($total > 0)
            <span @class(['hc-avail', 'is-full' => $free === 0])>{{ $property->vacancy_text ?? \App\Http\Controllers\Web\HomeCity\PropertyController::vacancyText($free, $selling) }}</span>
        @endif

        @if (!empty($property->location))
            <span class="hc-loc"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $property->location }}</span>
        @endif
    </div>
</article>
