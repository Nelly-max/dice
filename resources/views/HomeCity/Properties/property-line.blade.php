{{-- The list layout of a Home City card (results pages, "list" view): photo, title, unit type
     and place, price and vacancy. The whole row opens the listing on this unit type. --}}
@php
    $url = route('homecity.listing.view', array_filter([$property->listing_id, 'unit' => $property->group ?? null]));
    $total = (int) ($property->units_total ?? 0);
    $free = (int) ($property->units_vacant ?? 0);
    $selling = ($property->kind ?? '') === 'sale' || ($property->type_label ?? '') === 'For Sale';
    $suffix = trim($property->price_suffix ?? '');
    $ranged = $property->min_price != $property->max_price && $property->max_price > 0;
@endphp
<a href="{{ $url }}" @class(['gp-card', 'lf-line', 'is-full' => $total > 0 && $free === 0])>
    <span class="gp-pic lf-photo" aria-hidden="true">
        <img src="{{ $property->display_image }}" alt="" loading="lazy">
    </span>
    <span class="gp-body">
        <span class="lf-title"><strong>{{ $property->display_title }}</strong><span class="gp-tag">{{ $property->type_label }}</span></span>
        <small class="lf-fact"><i class="{{ $property->icon ?? 'fa-solid fa-building' }}" aria-hidden="true"></i>{{ \Illuminate\Support\Str::ucfirst($property->unit_type) }}</small>
        @if (!empty($property->location))
            <small class="lf-fact"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $property->location }}</small>
        @endif
        <span class="gp-meta">
            <b>{{ $ranged ? 'From ' : '' }}KES {{ number_format($property->min_price) }}@if ($suffix !== '') {{ $suffix }}@endif</b>
            @if ($total > 0)
                <em>{{ $property->vacancy_text ?? \App\Http\Controllers\Web\HomeCity\PropertyController::vacancyText($free, $selling) }}</em>
            @endif
            @isset($property->distance_km)
                <em class="gp-dist"><i class="fa-solid fa-location-dot"></i>{{ $property->distance_km < 1 ? round($property->distance_km * 1000, -1) . ' m' : number_format($property->distance_km, 1) . ' km' }}</em>
            @endisset
        </span>
    </span>
</a>
