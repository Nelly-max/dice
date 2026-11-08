@extends('HomeCity.layouts.app')

@section('title', $property->title ?? 'Listing')
@section('body_class', 'hc-has-mbar')

@section('content')
<main class="wrapper hc-page">
{{--
    One listing (PropertyController@viewListing).
    Hooks kept for reused.js: #imageSlider (photos) + #photoCounter, .tab_btn / .tab / .line (tabs).
--}}
@php
    $hero = $property->display_image;
    $photos = collect([['url' => $hero, 'label' => null]])
        ->filter(fn ($p) => !empty($p['url']))
        ->concat(collect($property->gallery_list ?? [])->reject(fn ($img) => ($img['url'] ?? null) === $hero))
        ->values();

    $priceFrom = (float) ($property->min_price ?? 0);
    $priceTo = (float) ($property->max_price ?? 0);
    $priceText = $priceFrom > 0
        ? 'KES ' . number_format($priceFrom) . ($priceTo > $priceFrom ? ' – ' . number_format($priceTo) : '')
        : 'Price on request';
    $suffix = trim($property->price_suffix ?? '');

    $lister = $listing->lister ?? null;
    $phone = trim((string) ($lister->phone ?? ''));
    $intl = $phone !== '' ? preg_replace('/^0/', '254', preg_replace('/\D/', '', $phone)) : null;


    // one card per unit type ("2 Bedroom · 3 of 8 vacant"), PropertyController::unitGroups
    $groups = collect($property->unit_groups ?? []);
    $unitsTotal = $groups->sum('total');
    $vacant = $groups->sum('vacant');

    // opened from a card on the Home City page: start on the Units tab with that type marked
    $picked = (string) request('unit', '');
    $openUnits = $picked !== '' && $groups->contains('key', $picked);
    $isSale = fn ($g) => $g->kind === 'sale' || ($property->type_label ?? '') === 'For Sale';
@endphp

<nav class="sv-crumbs" aria-label="Breadcrumb">
    <a href="{{ route('homecity.index') }}">Home City</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span>{{ $property->title }}</span>
</nav>

{{-- photos --}}
<div class="hc-gallery">
    <section @class(['hc-photos', 'is-single' => $photos->count() === 1]) id="imageSlider" aria-label="Photos">
        @forelse ($photos as $photo)
            <a href="{{ $photo['url'] }}">
                <img src="{{ $photo['url'] }}" alt="" @if (!$loop->first) loading="lazy" @endif>
                @if (!empty($photo['label']))
                    <span class="hc-photo-label">{{ ucfirst($photo['label']) }}</span>
                @endif
            </a>
        @empty
            <a href="#"><img src="{{ asset('img/default-property.jpg') }}" alt=""></a>
        @endforelse
    </section>

    <span class="hc-counter" id="photoCounter">1/{{ max(1, $photos->count()) }}</span>

    <div class="hc-gallery-bar">
        <div class="hc-gallery-actions">
            @if ($photos->count() > 5)
                <button type="button" class="sv-link-btn hc-view-all" data-hc-expand data-label="View all {{ $photos->count() }} photos">
                    <i class="fa-regular fa-images" aria-hidden="true"></i> <span>View all {{ $photos->count() }} photos</span>
                </button>
            @endif
            <button type="button" class="sv-link-btn" data-share
                    data-share-title="{{ $property->title }}"
                    data-share-text="{{ $property->title }} · {{ $priceText }} on Home City"
                    data-share-url="{{ url()->current() }}">
                <i class="fa-solid fa-share-nodes" aria-hidden="true"></i> Share
            </button>
        </div>
    </div>
</div>

{{-- title + price --}}
<div class="hc-head">
    <div>
        <div class="hc-tags">
            <span class="hc-tag"><i class="fa-solid fa-key" aria-hidden="true"></i> {{ $property->type_label ?? 'For Rent' }}</span>
            @if ($unitsTotal > 0)
                <span @class(['hc-tag', 'hc-tag--plain', 'hc-tag--none' => $vacant < 1])>{{ \App\Http\Controllers\Web\HomeCity\PropertyController::vacancyText($vacant, ($property->type_label ?? '') === 'For Sale') }}</span>
            @endif
        </div>
        <h1>{{ $property->title }}</h1>
        <p class="hc-location"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $property->formatted_location }}</p>
    </div>
    <div class="hc-head-price">
        <small>{{ $priceTo > $priceFrom ? 'Prices' : 'Price' }}</small>
        <strong>{{ $priceText }}</strong>
        @if ($suffix !== '')<em>{{ $suffix }}</em>@endif
    </div>
</div>

<div class="hc-layout">

    {{-- tabs --}}
    <section class="sv-panel">
        <div class="hc-tabs tab_btns" role="tablist">
            <button type="button" @class(['tab_btn', 'active' => !$openUnits])>Details</button>
            <button type="button" class="tab_btn">Amenities</button>
            <button type="button" @class(['tab_btn', 'active' => $openUnits])>Vacant units @if ($groups->isNotEmpty())({{ $vacant }})@endif</button>
            <button type="button" class="tab_btn">Charges</button>
            <div class="line"></div>
        </div>

        <div class="hc-tabbox tab_box">

            {{-- details --}}
            <div @class(['tab', 'active' => !$openUnits])>
                <h3>Overview</h3>
                <div class="hc-features">
                    @foreach ($groups as $group)
                        <span class="hc-feature"><i class="{{ $group->icon }}" aria-hidden="true"></i> {{ $group->label }}</span>
                    @endforeach
                </div>

                @if (!empty($property->description))
                    <p>{{ $property->description }}</p>
                @endif

                <h3>Location</h3>
                <p class="hc-location"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $property->formatted_location }}</p>
            </div>

            {{-- amenities --}}
            <div class="tab">
                @if ($property->amenities->isNotEmpty())
                    <div class="hc-amenities">
                        @foreach ($property->amenities as $amenity)
                            <span class="hc-amenity"><i class="fa-regular fa-circle-check" aria-hidden="true"></i> {{ $amenity->name }}</span>
                        @endforeach
                    </div>
                @else
                    <p>No amenities listed.</p>
                @endif
            </div>

            {{-- vacant units, listed one by one under their unit type --}}
            <div @class(['tab', 'active' => $openUnits])>
                @if ($groups->isNotEmpty())
                    <div class="hc-vacancies">
                        @foreach ($groups as $group)
                            @php
                                $sale = $isSale($group);
                                $suffixText = trim($group->suffix ?? '');
                                $list = collect($group->vacant_units ?? []);
                            @endphp
                            <section @class(['hc-vgroup', 'is-picked' => $picked === $group->key, 'is-full' => $group->vacant < 1]) @if ($picked === $group->key) aria-current="true" @endif>
                                <header class="hc-vgroup-head">
                                    <strong><i class="{{ $group->icon }}" aria-hidden="true"></i> {{ $group->label }}</strong>
                                    <span class="hc-vgroup-price">{{ $group->price_display }}</span>
                                    <span @class(['hc-avail', 'is-full' => $group->vacant < 1])>{{ \App\Http\Controllers\Web\HomeCity\PropertyController::vacancyText((int) $group->vacant, $sale) }}</span>
                                </header>

                                @if ($list->isNotEmpty())
                                    <ul class="hc-vlist">
                                        @foreach ($list as $unit)
                                            <li>
                                                <span class="hc-vunit">
                                                    <b>{{ $unit->name }}</b>
                                                    @if ($unit->detail)<small>{{ $unit->detail }}</small>@endif
                                                </span>
                                                <span class="hc-vprice">@if ($unit->price > 0)KES {{ number_format($unit->price) }}@if ($suffixText !== '') <em>{{ $suffixText }}</em>@endif @else Price on request @endif</span>
                                                @if ($intl)
                                                    <a href="https://wa.me/{{ $intl }}?text={{ rawurlencode('Hi, I saw "' . $property->title . '" on Home City. Is ' . $unit->name . ' (' . $group->label . ') still available?') }}"
                                                       class="sv-btn sv-btn--primary" target="_blank" rel="noopener">{{ $sale ? 'Enquire' : 'Book' }}</a>
                                                @else
                                                    <button type="button" class="sv-btn" disabled>{{ $sale ? 'Enquire' : 'Book' }}</button>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif ($group->kind === 'container' && $group->vacant > 0 && $intl)
                                    <a href="https://wa.me/{{ $intl }}?text={{ rawurlencode('Hi, I saw "' . $property->title . '" on Home City. Is a ' . $group->label . ' still available?') }}"
                                       class="sv-btn sv-btn--primary hc-vgroup-cta" target="_blank" rel="noopener">Book a {{ $group->label }}</a>
                                @endif
                            </section>
                        @endforeach
                    </div>
                @else
                    <p>No units listed.</p>
                @endif
            </div>

            {{-- charges --}}
            <div class="tab">
                <h3>Extra charges</h3>
                <dl class="sv-rows">
                    <div><dt>Garbage collection</dt><dd>KES 300</dd></div>
                    <div><dt>Viewing fee</dt><dd>KES 100</dd></div>
                </dl>
                <h3>Bills</h3>
                <dl class="sv-rows">
                    <div><dt>Electricity</dt><dd>Tokens</dd></div>
                    <div><dt>Water</dt><dd>Metered</dd></div>
                </dl>
            </div>

        </div>
    </section>

    {{-- lister --}}
    <aside class="sv-panel hc-lister" aria-label="Listed by">
        <div class="hc-lister-top">
            <img src="{{ asset('img/admin.png') }}" alt="">
            <div>
                <small>Listed by</small>
                <h2>{{ $lister->business_name ?? 'Lister' }}</h2>
            </div>
        </div>

        @if ($phone !== '')
            <p class="hc-lister-phone"><i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $phone }}</p>
            <div class="hc-lister-btns">
                <a href="tel:+{{ $intl }}" class="sv-btn sv-btn--primary"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call</a>
                <a href="https://wa.me/{{ $intl }}?text={{ rawurlencode('Hi, I saw "' . $property->title . '" on Home City. Is it still available?') }}"
                   class="sv-btn" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
            </div>
        @else
            <p class="hc-lister-phone"><i class="fa-solid fa-phone-slash" aria-hidden="true"></i> Phone not listed</p>
        @endif

        <p class="hc-lister-note">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span>Visit the property before you pay any deposit, and only pay the lister through official channels.</span>
        </p>
    </aside>
</div>

{{-- phones: price + call always in reach --}}
<div class="sv-mbar">
    <span>
        <small>{{ $property->type_label ?? 'For Rent' }}</small>
        <strong>{{ $priceText }}</strong>
    </span>
    @if ($intl)
        <a href="tel:+{{ $intl }}" class="sv-btn sv-btn--primary"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call lister</a>
    @endif
</div>

</main>

@endsection
