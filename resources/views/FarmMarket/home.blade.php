@extends('FarmMarket.layouts.app')

@section('content')

<main class="wrapper">

    @include('FarmMarket.partials.hero', ['slides' => $hero])

    {{-- One slot under the hero: "Set your delivery location" (or the cart-shop / out-of-range
         notice) until a location is saved, then the shops near that location take its place. --}}
    @if (!$hasLocation || $outOfRange || $cartShopName)
        @include('FarmMarket.partials.context-banner')
    @else
        @include('FarmMarket.partials.shops-near')
    @endif

    @include('FarmMarket.partials.category-tiles')

    @isset($shop)
        @include('shop.offers-strip')
    @endisset

    @include('FarmMarket.partials.product-row', ['title' => 'Fresh in store', 'products' => $rows['fresh']])

    @include('FarmMarket.partials.leaflets', ['images' => $leaflets[1]])

    @include('FarmMarket.partials.leaflets', ['images' => $leaflets[2]])

    {{-- a row for each of the biggest categories, with "See all" (ShopPages::shopHome) --}}
    @foreach (collect($shop['categoryRows'] ?? [])->take(2) as $catRow)
        @include('FarmMarket.partials.product-row', ['title' => $catRow->title, 'products' => $catRow->products, 'more' => $catRow->url])
    @endforeach

    @include('FarmMarket.partials.product-row', ['id' => 'hot-deals', 'title' => 'Hot deals', 'products' => $rows['hot'], 'more' => isset($shop) ? $shop['dealsUrl'] : null, 'endsAt' => $shop['endsAt'] ?? null])

    @include('FarmMarket.partials.banners', ['images' => $banners[1], 'class' => 'static_two'])

    @include('FarmMarket.partials.product-row', ['title' => 'Everyday products', 'products' => $rows['everyday']])

    @foreach (collect($shop['categoryRows'] ?? [])->slice(2) as $catRow)
        @include('FarmMarket.partials.product-row', ['title' => $catRow->title, 'products' => $catRow->products, 'more' => $catRow->url])
    @endforeach

    @include('FarmMarket.partials.banners', ['images' => $banners[2], 'class' => 'static_one'])

    @include('FarmMarket.partials.leaflets', ['images' => $leaflets[3]])

    @include('FarmMarket.partials.banners', ['images' => $banners[3], 'class' => 'static_three'])

</main>

@endsection
