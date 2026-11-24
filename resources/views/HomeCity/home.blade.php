@extends('HomeCity.layouts.app')

@section('title', request()->filled('q') ? 'Search: ' . request('q') : 'Homes & properties')

@section('content')
{{--
    Home City (PropertyController@index). Same structure as Home Market's home: hero slider,
    info bar, tiles, auto-sliding rows of cards, leaflets, banners.
    ?q=… (header search) or ?type=… (header strip / tiles) show a results page instead:
    filter chips (property type from the database, then unit type: ?pt= / ?ut=,
    App\Support\ListingFilters) and a grid / list switch (?view=, remembered).

    $mode (PropertyController): 'near' = Near me (nearest first, distance chips, asks for a
    location when none is set); 'lister' = one lister's profile ($lister) with their listings.

    Images: drop files into {MEDIA_ROOT}/img/homeCity/<folder> (Sliders, Carousel_1, StaticBanners_1).
    Until a folder has images, the bundled ones are shown.
--}}
@php
    $media = function (string $folder, array $defaults) {
        return \Illuminate\Support\Facades\Cache::remember("homecity.gallery.{$folder}." . (int) @filemtime(rtrim((string) config('app.media_root'), '/\\') . "/img/homeCity/{$folder}"), 300, function () use ($folder, $defaults) {
            $root = (string) config('app.media_root');
            $files = $root ? (glob(rtrim($root, '/\\') . "/img/homeCity/{$folder}/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", GLOB_BRACE) ?: []) : [];
            natsort($files);
            if ($files) {
                $base = rtrim(config('app.media_url'), '/') . "/img/homeCity/{$folder}/";
                return array_values(array_map(fn ($f) => ['url' => $base . rawurlencode(basename($f)), 'alt' => pathinfo($f, PATHINFO_FILENAME)], $files));
            }
            return array_map(fn ($p) => ['url' => asset($p), 'alt' => ''], $defaults);
        });
    };

    $hero = $media('Sliders', ['img/homeCity/Hero_Banner_1.png', 'img/homeCity/Hero_Banner_2.png']);
    $leaflets = $media('Carousel_1', ['img/homeMarket/LEAFLET_CARD_1.jpg', 'img/homeMarket/LEAFLET_CARD_2.png', 'img/homeMarket/LEAFLET_CARD_3.png']);
    $banners = $media('StaticBanners_1', []);

    // kind of each card (one card = one unit type of one property, PropertyController@index)
    $kindOf = fn ($p) => match ($p->kind ?? '') {
        'house', 'sale' => 'home',
        'hostel' => 'hostel',
        'land' => 'land',
        'warehouse' => 'warehouse',
        'container' => 'container',
        default => 'commercial',
    };
    $dealOf = fn ($p) => ($p->type_label ?? '') === 'For Sale' ? 'sale' : 'rent';

    $all = collect($listings);

    // distance from the visitor's saved location (map sheet): 500 m … 5 km, or any distance
    $here = app(\App\Services\DeliveryLocationService::class)->get(request());
    $hasLocation = (bool) $here;
    $radius = $hasLocation ? \App\Support\Distance::radiusFromRequest(request('radius')) : null;
    if ($hasLocation) {
        $all = $all->map(function ($p) use ($here) {
            $p->distance_km = \App\Support\Distance::km($here['latitude'], $here['longitude'], $p->latitude ?? null, $p->longitude ?? null);
            return $p;
        });
    }
    $radiusCounts = ['any' => $all->count()];
    foreach (\App\Support\Distance::RADII as $m) {
        $radiusCounts[$m] = $all->filter(fn ($p) => isset($p->distance_km) && $p->distance_km <= $m / 1000)->count();
    }
    $totalListings = $all->count();
    // every listing is shown; a radius only applies once the visitor picks one. With a location, nearest first.
    if ($hasLocation && $radius) {
        $all = $all->filter(fn ($p) => isset($p->distance_km) && $p->distance_km <= $radius / 1000);
    }
    if ($hasLocation) {
        $all = $all->sortBy(fn ($p) => $p->distance_km ?? INF)->values();
    }
    $radiusBar = ['hasLocation' => $hasLocation, 'radius' => $radius, 'counts' => $radiusCounts, 'noun' => 'listing'];
    $q = trim((string) request('q', ''));
    $type = (string) request('type', '');

    $kinds = [
        'home' => ['Homes', 'fa-solid fa-house-chimney'],
        'hostel' => ['Hostels', 'fa-solid fa-bed'],
        'commercial' => ['Shops & offices', 'fa-solid fa-store'],
        'warehouse' => ['Warehouses', 'fa-solid fa-warehouse'],
        'container' => ['Containers', 'fa-solid fa-box'],
        'land' => ['Land', 'fa-solid fa-expand'],
    ];
    $typeLabels = ['rent' => 'For rent', 'sale' => 'For sale'] + array_map(fn ($k) => $k[0], $kinds);

    $mode = $mode ?? null;
    $lister = $lister ?? null;
    $near = $mode === 'near';
    $filtering = $mode !== null || $q !== '' || isset($typeLabels[$type]) || request()->filled('pt') || request()->filled('ut');

    // Near me: only listings with a known distance (a location is needed)
    if ($near) {
        $all = $hasLocation ? $all->filter(fn ($p) => isset($p->distance_km))->values() : collect();
    }
    $results = $all
        ->when(isset($typeLabels[$type]), fn ($c) => $c->filter(fn ($p) => in_array($type, ['rent', 'sale'], true) ? $dealOf($p) === $type : $kindOf($p) === $type))
        ->when($q !== '', function ($c) use ($q) {
            $words = preg_split('/\s+/', \Illuminate\Support\Str::lower($q));
            return $c->filter(function ($p) use ($words) {
                $text = \Illuminate\Support\Str::lower(implode(' ', [$p->display_title ?? '', $p->unit_type ?? '', $p->location ?? '', $p->type_label ?? '', $p->kind ?? '']));
                return collect($words)->every(fn ($w) => str_contains($text, $w));
            });
        })
        ->values();

    $filters = \App\Support\ListingFilters::apply($results, request());
    $results = $filters['items'];
    $view = \App\Support\ListingFilters::view(request());
    $heading = $q !== ''
        ? null
        : ($typeLabels[$type] ?? ($near ? 'Near you' : ($lister ? 'All listings' : ($filters['typeName'] ? \Illuminate\Support\Str::plural($filters['typeName']) : 'All listings'))));
    $listerIntl = $lister && $lister->phone ? preg_replace('/^0/', '254', preg_replace('/\D+/', '', (string) $lister->phone)) : null;
    $narrowed = collect([$filters['typeName'] && $heading !== \Illuminate\Support\Str::plural($filters['typeName']) ? $filters['typeName'] : null, $filters['unitName']])->filter();
@endphp

@if ($filtering)

    <main class="wrapper hm-results">
        @if ($lister)
            <nav class="sv-crumbs" aria-label="Breadcrumb">
                <a href="{{ route('homecity.index') }}">Home City</a>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <a href="{{ route('homecity.listers') }}">Listers</a>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <span>{{ $lister->name }}</span>
            </nav>

            {{-- who the lister is and how to reach them --}}
            <section class="gp-card lf-lister" aria-label="Lister">
                <span class="gp-mark lf-lister-mark" aria-hidden="true">
                    @if ($lister->logo)<img src="{{ $lister->logo }}" alt="" onerror="this.remove()">@endif
                    {{ $lister->initial }}
                </span>
                <span class="gp-body">
                    <strong>{{ $lister->name }}</strong>
                    @if ($lister->area !== '')
                        <small class="lf-fact"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>{{ $lister->area }}</small>
                    @endif
                    <span class="gp-meta"><em>{{ $all->pluck('listing_id')->unique()->count() }} {{ \Illuminate\Support\Str::plural('listing', $all->pluck('listing_id')->unique()->count()) }}</em><em>{{ (int) $all->sum('units_vacant') }} vacant</em></span>
                </span>
                @if ($listerIntl)
                    <span class="lf-lister-btns">
                        <a href="tel:+{{ $listerIntl }}" class="sv-btn sv-btn--primary"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call</a>
                        <a href="https://wa.me/{{ $listerIntl }}?text={{ rawurlencode('Hi, I saw your listings on Home City.') }}" class="sv-btn" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
                    </span>
                @endif
            </section>
        @endif

        <header class="hm-results-head lf-head">
            <div>
                <h1>
                    @if ($q !== '') Results for <em>“{{ $q }}”</em>@if (isset($typeLabels[$type])) in {{ $typeLabels[$type] }}@endif
                    @else {{ $heading }}
                    @endif
                </h1>
                @if ($near && !$hasLocation)
                    <p>Homes and spaces closest to you, nearest first</p>
                @else
                    <p>{{ $results->count() }} {{ \Illuminate\Support\Str::plural('listing', $results->count()) }}@if ($narrowed->isNotEmpty()) · {{ $narrowed->implode(' · ') }}@endif</p>
                @endif
            </div>
            @unless ($near && !$hasLocation)
                @include('partials.view-toggle', ['view' => $view])
            @endunless
        </header>

        {{-- Near me keeps the distance chips (and asks for a location when none is set) --}}
        @if ($near && $hasLocation)
            @include('partials.radius-bar', $radiusBar)
        @endif

        {{-- property type, then unit type (in place of the location bar) --}}
        @include('partials.listing-filters', ['filters' => $filters, 'noun' => 'listing'])

        @if ($results->isNotEmpty())
            @if ($view === 'list')
                <div class="gp-grid lf-list">
                    @foreach ($results as $listing)
                        @include('HomeCity.Properties.property-line', ['property' => $listing])
                    @endforeach
                </div>
            @else
                <div class="hm-grid hc-grid">
                    @foreach ($results as $listing)
                        @include('HomeCity.Properties.property-card', ['property' => $listing])
                    @endforeach
                </div>
            @endif
        @elseif ($near)
            {{-- same "radar" empty state as the gas / water shops (partials/shops-empty) --}}
            @include('partials.shops-empty', $hasLocation ? [
                'state' => 'far',
                'mark' => 'home',
                'radiusKm' => $radius ? $radius / 1000 : null,
                'title' => $radius ? 'Nothing within ' . \App\Support\Distance::label($radius) . ' of you' : 'Nothing near you yet',
                'text' => $radius ? 'Pick a wider distance above, or look around another spot.' : 'No listings have a map location near here yet. New homes and spaces are added every week.',
                'button' => 'Try another location',
                'homeUrl' => route('homecity.index'),
                'homeLabel' => 'all listings',
                'tips' => [
                    ['fa-solid fa-briefcase', 'Looking near work or school? Set that spot instead.'],
                    ['fa-solid fa-user-tie', 'Browse listers to see everything an agent or landlord has.'],
                ],
            ] : [
                'state' => 'noloc',
                'mark' => 'home',
                'title' => 'Where are you?',
                'text' => "Set your location and we'll show the homes and spaces closest to you, nearest first.",
                'button' => 'Set my location',
                'homeUrl' => route('homecity.index'),
                'homeLabel' => 'all listings',
                'tips' => [
                    ['fa-solid fa-briefcase', 'Looking near work or school? Set that spot instead of where you are now.'],
                    ['fa-solid fa-ruler', 'Then narrow it down: 500 m, 1 km, 2 km, 3 km or 5 km.'],
                ],
            ])
        @else
            <div class="hm-empty">
                <i class="fa-solid fa-house-circle-xmark" aria-hidden="true"></i>
                <h2>No listings found</h2>
                <p>{{ $radius ? 'Nothing within ' . \App\Support\Distance::label($radius) . ' of you. Try a wider distance, another area or type.' : 'Try another area or property type.' }}</p>
                <a class="hm-btn" href="{{ route('homecity.index') }}">See all listings</a>
            </div>
        @endif
    </main>

@else

    <main class="wrapper">

        @include('HomeMarket.partials.hero', ['slides' => $hero])

        @include('partials.radius-bar', $radiusBar)

        <div class="info-bar">
            <a href="{{ route('homecity.index', ['type' => 'rent']) }}" class="info">
                <i class="fa-solid fa-key" style="--icon: #0f766e" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Homes to rent</h3>
                    <h4>See vacant units first</h4>
                </div>
            </a>
            <a href="{{ route('homecity.index', ['type' => 'sale']) }}" class="info">
                <i class="fa-solid fa-tag" style="--icon: #00c2d1" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Property for sale</h3>
                    <h4>Houses and land</h4>
                </div>
            </a>
            <a href="{{ route('homecity.listers') }}" class="info">
                <i class="fa-solid fa-phone" style="--icon: #00d26a" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Talk to the lister</h3>
                    <h4>Call or WhatsApp directly</h4>
                </div>
            </a>
        </div>

        <section class="category-icons main-cat-icons hc-tiles" aria-label="Property types">
            @foreach ($kinds as $key => [$label, $icon])
                <a href="{{ route('homecity.index', ['type' => $key]) }}" class="category-icon">
                    <span class="hc-tile-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </section>

        @include('HomeCity.partials.listing-row', ['id' => 'top-listings', 'title' => $hasLocation ? 'Nearest to you' : 'Top listings', 'items' => $all->take(20)])

        @include('HomeMarket.partials.leaflets', ['images' => $leaflets])

        @include('HomeCity.partials.listing-row', ['title' => 'Homes to rent', 'items' => $all->filter(fn ($p) => $dealOf($p) === 'rent' && $kindOf($p) === 'home')->values()])

        @include('HomeCity.partials.listing-row', ['title' => 'For sale', 'items' => $all->filter(fn ($p) => $dealOf($p) === 'sale')->values()])

        @include('HomeMarket.partials.banners', ['images' => $banners, 'class' => 'static_two'])

        @include('HomeCity.partials.listing-row', ['title' => 'Hostels', 'items' => $all->filter(fn ($p) => $kindOf($p) === 'hostel')->values()])

        @include('HomeCity.partials.listing-row', ['title' => 'Shops & offices', 'items' => $all->filter(fn ($p) => $kindOf($p) === 'commercial')->values()])

        @include('HomeCity.partials.listing-row', ['title' => 'Warehouses', 'items' => $all->filter(fn ($p) => $kindOf($p) === 'warehouse')->values()])

        @include('HomeCity.partials.listing-row', ['title' => 'Containers', 'items' => $all->filter(fn ($p) => $kindOf($p) === 'container')->values()])

        @include('HomeCity.partials.listing-row', ['title' => 'Land', 'items' => $all->filter(fn ($p) => $kindOf($p) === 'land')->values()])

        @if ($all->isEmpty())
            <div class="hm-empty">
                <i class="fa-solid fa-city" aria-hidden="true"></i>
                @if ($radius && $totalListings > 0)
                    <h2>Nothing within {{ \App\Support\Distance::label($radius) }}</h2>
                    <p>No listings this close to your location yet.</p>
                    <a class="hm-btn" href="{{ request()->fullUrlWithQuery(['radius' => 'any']) }}">Show all distances</a>
                @else
                    <h2>No listings yet</h2>
                    <p>New homes and spaces will appear here soon.</p>
                @endif
            </div>
        @endif

    </main>

@endif

@endsection
