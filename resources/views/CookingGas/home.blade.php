@extends('CookingGas.layouts.app')

@section('title', 'Gas refills delivered')

@section('content')
{{--
    Cooking Gas home (ProductController@index). Same structure as Home Market's home:
    hero slider, location banner / info bar, tiles, auto-sliding rows of cards, leaflets, banners.

    Images: drop files into {MEDIA_ROOT}/img/cookingGas/<folder> (Sliders, Carousel_1, Carousel_2,
    StaticBanners_1, StaticBanners_2). Until a folder has images, the bundled ones are shown.
--}}
@php
    $media = function (string $folder, array $defaults) {
        return \Illuminate\Support\Facades\Cache::remember("cookinggas.gallery.{$folder}", 300, function () use ($folder, $defaults) {
            $root = (string) config('app.media_root');
            $files = $root ? (glob(rtrim($root, '/\\') . "/img/cookingGas/{$folder}/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}", GLOB_BRACE) ?: []) : [];
            natsort($files);
            if ($files) {
                $base = rtrim(config('app.media_url'), '/') . "/img/cookingGas/{$folder}/";
                return array_values(array_map(fn ($f) => ['url' => $base . rawurlencode(basename($f)), 'alt' => pathinfo($f, PATHINFO_FILENAME)], $files));
            }
            return array_map(fn ($p) => ['url' => asset($p), 'alt' => ''], $defaults);
        });
    };

    $hero = $media('Sliders', ['img/homeMarket/Hero_Banner_1.jpg', 'img/homeMarket/Hero_Banner_2.jpg', 'img/homeMarket/Hero_Banner_3.jpg']);
    $leaflets1 = $media('Carousel_1', ['img/homeMarket/LEAFLET_CARD_1.jpg', 'img/homeMarket/LEAFLET_CARD_2.png', 'img/homeMarket/LEAFLET_CARD_3.png', 'img/homeMarket/LEAFLET_CARD_4.png', 'img/homeMarket/LEAFLET_CARD_5.png', 'img/homeMarket/LEAFLET_CARD_6.png']);
    $leaflets2 = $media('Carousel_2', []);
    $banners1 = $media('StaticBanners_1', ['img/homeMarket/STATIC_BANNER_1.jpg', 'img/homeMarket/STATIC_BANNER_2.jpg']);
    $banners2 = $media('StaticBanners_2', []);

    $all = collect($cylinders);
    $hasLocation = app(\App\Services\DeliveryLocationService::class)->exists(request());

    // one row per brand, cheapest first; the header's brand links jump to these rows
    $byBrand = $all->groupBy(fn ($c) => $c->cylinder->brand_name ?? 'Other')->sortKeys();
    $gasBrands = $byBrand->keys()->mapWithKeys(fn ($b) => [$b => 'brand-' . \Illuminate\Support\Str::slug($b)])->all();

    // brand tiles: the first cylinder photo of each brand
    $tiles = $byBrand->map(fn ($items, $brand) => [
        'label' => $brand,
        'anchor' => $gasBrands[$brand],
        'image' => ($img = $items->firstWhere('image')?->image)
            ? rtrim(config('app.media_url'), '/') . '/' . ltrim($img, '/')
            : asset('img/logo/cookingGas.png'),
    ])->values();

    $best = $all->sortBy('min_price')->take(20)->values();
@endphp

<main class="wrapper">

    @include('HomeMarket.partials.hero', ['slides' => $hero])

    {{-- one slot under the hero, like Home Market: set location / nothing in range / shopping at
         the cart's shop, otherwise the gas shops near the buyer --}}
    @if (!$hasLocation || ($outOfRange ?? false) || !empty($cartShopName))
        @include('HomeMarket.partials.context-banner', ['outOfRange' => $outOfRange ?? false, 'cartShopName' => $cartShopName ?? null])
    @else
        @include('CookingGas.partials.shops-near', ['nearbyShops' => $nearbyShops ?? collect(), 'radiusKm' => $radiusKm ?? 3])
    @endif

    <div class="info-bar">
        <a href="#best-prices" class="info">
            <i class="fa-solid fa-motorcycle" style="--icon: #1d63d8" aria-hidden="true"></i>
            <div class="info-txt">
                <h3>Quick delivery</h3>
                <h4>Refills in 10 – 60 minutes</h4>
            </div>
        </a>
        <a href="#best-prices" class="info">
            <i class="fa-solid fa-scale-balanced" style="--icon: #00c2d1" aria-hidden="true"></i>
            <div class="info-txt">
                <h3>Compare prices</h3>
                <h4>Pick the best gas shop</h4>
            </div>
        </a>
        <a href="{{ route('page', 'help') }}" class="info">
            <i class="fa-solid fa-mobile-screen-button" style="--icon: #00d26a" aria-hidden="true"></i>
            <div class="info-txt">
                <h3>Pay with M-Pesa</h3>
                <h4>Prompt or Paybill</h4>
            </div>
        </a>
    </div>

    @if ($tiles->count() > 1)
        <section class="category-icons main-cat-icons" aria-label="Gas brands">
            @foreach ($tiles as $tile)
                <a href="#{{ $tile['anchor'] }}" class="category-icon">
                    <img src="{{ $tile['image'] }}" alt="" loading="lazy">
                    <span>{{ $tile['label'] }}</span>
                </a>
            @endforeach
        </section>
    @endif

    @include('CookingGas.partials.product-row', ['id' => 'best-prices', 'title' => 'Best prices', 'cylinders' => $best])

    @include('HomeMarket.partials.leaflets', ['images' => $leaflets1])

    @foreach ($byBrand as $brand => $items)
        @include('CookingGas.partials.product-row', [
            'id' => $gasBrands[$brand],
            'title' => $brand . ' refills',
            'cylinders' => $items->sortBy('min_price')->values(),
        ])

        @if ($loop->iteration === 2)
            @include('HomeMarket.partials.banners', ['images' => $banners1, 'class' => 'static_two'])
        @endif
    @endforeach

    @if ($byBrand->count() < 2)
        @include('HomeMarket.partials.banners', ['images' => $banners1, 'class' => 'static_two'])
    @endif

    @include('HomeMarket.partials.leaflets', ['images' => $leaflets2])

    @include('HomeMarket.partials.banners', ['images' => $banners2, 'class' => 'static_one'])

    @if ($all->isEmpty())
        <div class="hm-empty">
            <i class="fa-solid fa-fire-flame-simple" aria-hidden="true"></i>
            <h2>{{ ($outOfRange ?? false) ? 'No gas shops near you yet' : 'No gas refills yet' }}</h2>
            <p>{{ ($outOfRange ?? false) ? 'No open gas shop delivers to this location yet. Try a different delivery location.' : 'Gas shops near you will appear here soon.' }}</p>
        </div>
    @endif

</main>

@endsection
