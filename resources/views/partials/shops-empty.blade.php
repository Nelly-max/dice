{{--
    "No shops near you": the empty state of the Shops pages (shop/shops, Water and Cooking Gas).
    A small radar drawing: the buyer's pin in the middle, the delivery circle around it and the
    nearest shops sitting just outside it, then what to do next. Styles: .se-* in shop-pages.css.

    @include('partials.shops-empty', [
        'what' => 'water shops',            // what is missing, lower case
        'state' => 'far',                    // far: location set, none in range | none: no shops at all | noloc: no location yet
        'radiusKm' => 3,
        'homeUrl' => route('water.index'),
        'homeLabel' => 'Water',
    ])

    Optional, for pages that are not about delivery (Home City "Near me"):
        'title' => 'Where are you?', 'text' => '…', 'button' => 'Set my location',
        'tips' => [['fa-solid fa-briefcase', 'Looking near work? Set that spot instead.'], …],
        'mark' => 'home'                     // houses around the pin instead of shops
--}}
@php
    $what = $what ?? 'shops';
    $state = $state ?? 'far';
    $radiusKm = $radiusKm ?? null;
    $radiusText = $radiusKm ? rtrim(rtrim(number_format((float) $radiusKm, 1), '0'), '.') . ' km' : null;
    $home = ($mark ?? 'shop') === 'home';
    $tips = $tips ?? ($state === 'far' ? [
        ['fa-solid fa-map-location-dot', "Delivering to work or a friend's place? Set that spot instead."],
        ['fa-solid fa-store', "Shops only show here while they're open and selling online."],
    ] : []);
@endphp
<section class="se-empty se-empty--{{ $state }}" aria-labelledby="seTitle">

    <div class="se-art" aria-hidden="true">
        <svg viewBox="0 0 220 220" role="presentation">
            <circle class="se-ring se-ring--3" cx="110" cy="110" r="100"/>
            <circle class="se-ring se-ring--2" cx="110" cy="110" r="70"/>
            <circle class="se-zone" cx="110" cy="110" r="42"/>
            <circle class="se-pulse" cx="110" cy="110" r="42"/>
            {{-- shops: outside the delivery circle (far) or nowhere yet (none) --}}
            <g class="se-shops">
                @if ($home)
                    <g transform="translate(176 52)"><circle r="15"/><path d="M-7 0l7 -7l7 7 M-5 -1v8h10v-8 M-1.5 7v-4h3v4" /></g>
                    <g transform="translate(36 150)"><circle r="13"/><path d="M-6 0l6 -6l6 6 M-4.5 -1v7h9v-7" /></g>
                    <g transform="translate(182 168)"><circle r="11"/><path d="M-5 0l5 -5l5 5 M-3.5 -1v5.5h7v-5.5" /></g>
                @else
                    <g transform="translate(176 52)"><circle r="15"/><path d="M-7 -2h14l-2 -6h-10z M-6 0v7h12v-7" /></g>
                    <g transform="translate(36 150)"><circle r="13"/><path d="M-6 -2h12l-2 -5h-8z M-5 0v6h10v-6" /></g>
                    <g transform="translate(182 168)"><circle r="11"/><path d="M-5 -1.5h10l-1.5 -4h-7z M-4 0v5h8v-5" /></g>
                @endif
            </g>
            <g class="se-pin" transform="translate(110 104)">
                <path d="M0 22c-1.5 0-16-15-16-27a16 16 0 0 1 32 0c0 12-14.5 27-16 27z"/>
                <circle cx="0" cy="-5" r="6"/>
            </g>
        </svg>
        @if ($radiusText && $state === 'far')
            <b class="se-radius">{{ $radiusText }}</b>
        @endif
    </div>

    <div class="se-text">
        @if (!empty($title))
            <h2 id="seTitle">{{ $title }}</h2>
            @if (!empty($text))<p>{{ $text }}</p>@endif
        @elseif ($state === 'noloc')
            <h2 id="seTitle">Where should we deliver?</h2>
            <p>Set your delivery location and we'll show the {{ $what }} closest to you, nearest first.</p>
        @elseif ($state === 'far')
            <h2 id="seTitle">No {{ $what }} near you yet</h2>
            <p>
                None of the open {{ $what }} deliver to this location{{ $radiusText ? ' (we look ' . $radiusText . ' around you)' : '' }}.
                New shops join every week, so check back soon.
            </p>
        @else
            <h2 id="seTitle">No {{ $what }} yet</h2>
            <p>{{ ucfirst($what) }} appear here as soon as they start selling online.</p>
        @endif

        <div class="se-actions">
            @if ($state !== 'none')
                <button type="button" class="se-btn se-btn--main" onclick="showModal('mapModal')">
                    <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                    {{ $button ?? ($state === 'noloc' ? 'Set delivery location' : 'Try another location') }}
                </button>
            @endif
            @if (!empty($homeUrl))
                <a class="se-btn se-btn--ghost" href="{{ $homeUrl }}">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to {{ $homeLabel ?? 'the shop' }}
                </a>
            @endif
        </div>

        @if ($tips)
            <ul class="se-tips">
                @foreach ($tips as [$tipIcon, $tipText])
                    <li><i class="{{ $tipIcon }}" aria-hidden="true"></i> {{ $tipText }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
