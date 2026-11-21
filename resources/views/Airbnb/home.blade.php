@extends('Airbnb.layouts.app')

@section('title', $q !== '' ? 'Search: ' . $q : 'Short stays')

@section('content')
{{--
    Airbnb home (Airbnb\StayController@index). Same structure as Home Market's home: hero,
    distance bar (500 m … 5 km from the visitor's location), info bar, type tiles, rows of cards.
    ?q= (header search) or ?type= (header strip / tiles) show a results page instead: filter
    chips (house type, then unit type: ?pt= / ?ut=) and a grid / list switch (?view=).
--}}
@php
    // Images uploaded from the hub (Home → Banners) into {MEDIA_ROOT}/img/airbnb/<folder>; built-in ones until then
    $media = function (string $folder, array $defaults) {
        return \Illuminate\Support\Facades\Cache::remember("airbnb.gallery.{$folder}." . (int) @filemtime(rtrim((string) config('app.media_root'), '/\\') . "/img/airbnb/{$folder}"), 300, function () use ($folder, $defaults) {
            $root = (string) config('app.media_root');
            $files = $root ? (glob(rtrim($root, '/\\') . "/img/airbnb/{$folder}/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", GLOB_BRACE) ?: []) : [];
            natsort($files);
            if ($files) {
                $base = rtrim(config('app.media_url'), '/') . "/img/airbnb/{$folder}/";
                return array_values(array_map(fn ($f) => ['url' => $base . rawurlencode(basename($f)), 'alt' => pathinfo($f, PATHINFO_FILENAME)], $files));
            }
            return array_map(fn ($p) => ['url' => asset($p), 'alt' => ''], $defaults);
        });
    };
    $hero = $media('Sliders', ['img/homeCity/HOMECITY_MAIN_IMG_001.jpg', 'img/homeCity/HOMECITY_BEDROOM_001.jpg', 'img/homeCity/H001VE001.png']);
    $leaflets = $media('Carousel_1', []);
    $banners = $media('StaticBanners_1', []);
    $radius = $radiusBar['radius'];
@endphp

<main @class(['wrapper', 'hm-results' => $filtering])>

    @if ($sample ?? false)
        @include('partials.preview-banner', ['text' => 'These are sample stays to show how Airbnb on Smart Market will work. Booking opens soon.'])
    @endif

    @if ($filtering)

        @php
            $heading = $types[$type][0] ?? ($filters['typeName'] ? \Illuminate\Support\Str::plural($filters['typeName']) : 'All stays');
            $narrowed = collect([$filters['typeName'] && $heading !== \Illuminate\Support\Str::plural($filters['typeName']) ? $filters['typeName'] : null, $filters['unitName']])->filter();
        @endphp

        <header class="hm-results-head lf-head">
            <div>
                <h1>
                    @if ($q !== '') Results for <em>“{{ $q }}”</em>@if (isset($types[$type])) in {{ $types[$type][0] }}@endif
                    @else {{ $heading }}
                    @endif
                </h1>
                <p>{{ $results->count() }} {{ \Illuminate\Support\Str::plural('stay', $results->count()) }}@if ($narrowed->isNotEmpty()) · {{ $narrowed->implode(' · ') }}@endif</p>
            </div>
            @include('partials.view-toggle', ['view' => $view])
        </header>

        {{-- house type, then unit type (in place of the location bar) --}}
        @include('partials.listing-filters', ['filters' => $filters, 'noun' => 'stay'])

        @if ($results->isNotEmpty())
            @if ($view === 'list')
                <div class="gp-grid lf-list">
                    @foreach ($results as $stay)
                        @include('Airbnb.partials.stay-line', ['stay' => $stay])
                    @endforeach
                </div>
            @else
                <div class="hm-grid hc-grid">
                    @foreach ($results as $stay)
                        @include('Airbnb.partials.stay-card', ['stay' => $stay])
                    @endforeach
                </div>
            @endif
        @else
            <div class="hm-empty">
                <i class="fa-solid fa-bed" aria-hidden="true"></i>
                <h2>No stays found</h2>
                <p>{{ $radius ? 'Nothing within ' . \App\Support\Distance::label($radius) . ' of you. Try a wider distance or another area.' : 'Try another area or stay type.' }}</p>
                <a class="hm-btn" href="{{ route('airbnb.index') }}">See all stays</a>
            </div>
        @endif

    @else

        @include('HomeMarket.partials.hero', ['slides' => $hero])

        @include('partials.radius-bar', $radiusBar)

        <div class="info-bar">
            <a href="#top-stays" class="info">
                <i class="fa-solid fa-calendar-check" style="--icon: #7c3aed" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Book by the night</h3>
                    <h4>Full price shown upfront</h4>
                </div>
            </a>
            <a href="#top-stays" class="info">
                <i class="fa-solid fa-images" style="--icon: #00c2d1" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Real photos</h3>
                    <h4>See every room first</h4>
                </div>
            </a>
            <a href="{{ route('page', 'help') }}" class="info">
                <i class="fa-solid fa-mobile-screen-button" style="--icon: #00d26a" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Pay with M-Pesa</h3>
                    <h4>Confirmed instantly</h4>
                </div>
            </a>
        </div>

        <section class="category-icons main-cat-icons hc-tiles" aria-label="Stay types">
            @foreach ($types as $key => [$label, $icon])
                <a href="{{ route('airbnb.index', ['type' => $key]) }}" class="category-icon">
                    <span class="hc-tile-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </section>

        @include('Airbnb.partials.stay-row', ['id' => 'top-stays', 'title' => $radiusBar['hasLocation'] ? 'Nearest to you' : 'Popular stays', 'items' => $radiusBar['hasLocation'] ? $stays : $stays->sortByDesc(fn ($s) => [(int) !empty($s->featured), (float) ($s->rating ?? 0)])->values()])

        @include('HomeMarket.partials.leaflets', ['images' => $leaflets])

        @include('Airbnb.partials.stay-row', ['title' => 'Under KES 5,000 a night', 'items' => $stays->where('price', '<', 5000)->values()])

        @include('Airbnb.partials.stay-row', ['title' => 'For families and groups', 'items' => $stays->filter(fn ($s) => ($s->guests ?? 0) >= 4 || ($s->bedrooms ?? 0) >= 2)->values()])

        @include('HomeMarket.partials.banners', ['images' => $banners, 'class' => 'static_two'])

        @include('Airbnb.partials.stay-row', ['title' => 'Featured stays', 'items' => $stays->filter(fn ($s) => !empty($s->featured) || !empty($s->superhost))->values()])

        @if ($stays->isEmpty())
            <div class="hm-empty">
                <i class="fa-solid fa-bed" aria-hidden="true"></i>
                @if ($radius && $totalStays > 0)
                    <h2>Nothing within {{ \App\Support\Distance::label($radius) }}</h2>
                    <p>No stays this close to your location yet.</p>
                    <a class="hm-btn" href="{{ request()->fullUrlWithQuery(['radius' => 'any']) }}">Show all distances</a>
                @else
                    <h2>No stays yet</h2>
                    <p>New stays will appear here soon.</p>
                @endif
            </div>
        @endif

    @endif

</main>

@endsection
