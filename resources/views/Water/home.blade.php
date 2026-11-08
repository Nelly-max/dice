@extends('Water.layouts.app')

@section('title', $q !== '' ? 'Search: ' . $q : 'Water delivery')

@section('content')
{{--
    Water home (Water\WaterController@index). Same structure as Cooking Gas / Home Market:
    hero, delivery-location banner or "Shops near you", info bar, option tiles, a row per option
    (purified water refill, bottled water, clean water by truck). ?q= (header search) shows a results grid.
--}}
@php
    // Images: drop files into {MEDIA_ROOT}/img/water/<folder> (Sliders, Carousel_1). Until then a plain banner shows.
    $media = function (string $folder) {
        return \Illuminate\Support\Facades\Cache::remember("water.gallery.{$folder}", 300, function () use ($folder) {
            $root = (string) config('app.media_root');
            $files = $root ? (glob(rtrim($root, '/\\') . "/img/water/{$folder}/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", GLOB_BRACE) ?: []) : [];
            natsort($files);
            $base = rtrim(config('app.media_url'), '/') . "/img/water/{$folder}/";
            return array_values(array_map(fn ($f) => ['url' => $base . rawurlencode(basename($f)), 'alt' => pathinfo($f, PATHINFO_FILENAME)], $files));
        });
    };
    $options = config('water.options', []);
@endphp

<main @class(['wrapper', 'hm-results' => $filtering])>

    @if ($filtering)

        <header class="hm-results-head">
            <h1>Results for <em>“{{ $q }}”</em></h1>
            <p>{{ $results->count() }} {{ \Illuminate\Support\Str::plural('result', $results->count()) }}</p>
        </header>

        @if ($results->isNotEmpty())
            <div class="hm-grid">
                @foreach ($results as $offer)
                    @include('Water.partials.product-card', ['offer' => $offer])
                @endforeach
            </div>
        @else
            <div class="hm-empty">
                <i class="fa-solid fa-droplet" aria-hidden="true"></i>
                <h2>Nothing found</h2>
                <p>Try “refill”, “20 L” or “truck”.</p>
                <a class="hm-btn" href="{{ route('water.index') }}">See all water</a>
            </div>
        @endif

    @else

        @php $hero = $media('Sliders'); @endphp
        @if (count($hero))
            @include('HomeMarket.partials.hero', ['slides' => $hero])
        @else
            <section class="soon-hero wt-hero" aria-label="Water delivery">
                <span class="soon-mark" aria-hidden="true"><i class="fa-solid fa-droplet"></i></span>
                <div>
                    <span class="soon-chip"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i> {{ config('water.delivery_eta') }}</span>
                    <h1>Clean water, delivered</h1>
                    <p>Refills, bottled water and water trucks from water shops near you. Pay with M-Pesa.</p>
                </div>
                <i class="fa-solid fa-bottle-water soon-art" aria-hidden="true"></i>
            </section>
        @endif

        {{-- same as Home Market / Cooking Gas: why you see what you see, else the shops near you --}}
        @if (!$hasLocation || $outOfRange || !empty($cartShopName))
            @include('HomeMarket.partials.context-banner', ['outOfRange' => $outOfRange, 'cartShopName' => $cartShopName])
        @else
            @include('Water.partials.shops-near', ['nearbyShops' => $nearbyShops, 'radiusKm' => $radiusKm])
        @endif

        <div class="info-bar">
            <a href="{{ route('water.refill') }}" class="info">
                <i class="fa-solid fa-motorcycle" style="--icon: #0891b2" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Quick delivery</h3>
                    <h4>{{ config('water.delivery_eta') }}</h4>
                </div>
            </a>
            <a href="{{ route('water.refill') }}" class="info">
                <i class="fa-solid fa-rotate" style="--icon: #00c2d1" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Refills</h3>
                    <h4>Swap your empty bottle</h4>
                </div>
            </a>
            <a href="{{ route('water.clean') }}" class="info">
                <i class="fa-solid fa-truck-droplet" style="--icon: #0284c7" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Water trucks</h3>
                    <h4>Clean water for estates</h4>
                </div>
            </a>
        </div>

        <section class="category-icons main-cat-icons hc-tiles" aria-label="Water">
            @foreach ($options as $key => $opt)
                <a href="{{ route($opt['route']) }}" class="category-icon">
                    <span class="hc-tile-icon" aria-hidden="true"><i class="{{ $opt['icon'] }}"></i></span>
                    <span>{{ $opt['label'] }}</span>
                </a>
            @endforeach
            <a href="{{ route('water.shops') }}" class="category-icon">
                <span class="hc-tile-icon" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
                <span>Shops near you</span>
            </a>
        </section>

        @foreach ($rows as $key => $row)
            @include('Water.partials.product-row', ['id' => 'wt-' . $key, 'title' => $row['meta']['label'], 'items' => $row['items'], 'more' => route($row['meta']['route'])])

            @if ($loop->first)
                @include('HomeMarket.partials.leaflets', ['images' => $media('Carousel_1')])
            @endif
        @endforeach

        @if ($rows->every(fn ($r) => $r['items']->isEmpty()))
            <div class="hm-empty">
                <i class="fa-solid fa-droplet" aria-hidden="true"></i>
                <h2>{{ $outOfRange ? 'No water shops near you yet' : 'No water here yet' }}</h2>
                <p>{{ $outOfRange ? 'No open water shop delivers to this location yet. Try a different delivery location.' : 'Water shops near you will appear here soon.' }}</p>
                @if ($outOfRange)
                    <button type="button" class="hm-btn" onclick="showModal('mapModal')">Change location</button>
                @endif
            </div>
        @endif

    @endif

</main>

@endsection
