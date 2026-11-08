@extends('LiquorHub.layouts.app')

@section('content')

<main class="wrapper">

    @include('LiquorHub.partials.hero', ['slides' => $hero])

    {{-- One slot under the hero: "Set your delivery location" (or the cart-shop / out-of-range
         notice) until a location is saved, then the shops near that location take its place. --}}
    @if (!$hasLocation || $outOfRange || $cartShopName)
        @include('LiquorHub.partials.context-banner')
    @else
        @include('LiquorHub.partials.shops-near')
    @endif

    @include('LiquorHub.partials.category-tiles')

    @isset($shop)
        @include('shop.offers-strip')
    @endisset

    @include('LiquorHub.partials.product-row', ['title' => 'Fresh in store', 'products' => $rows['fresh']])

    @include('LiquorHub.partials.leaflets', ['images' => $leaflets[1]])

    @include('LiquorHub.partials.leaflets', ['images' => $leaflets[2]])

    {{-- a row for each of the biggest categories, with "See all" (ShopPages::shopHome) --}}
    @foreach (collect($shop['categoryRows'] ?? [])->take(2) as $catRow)
        @include('LiquorHub.partials.product-row', ['title' => $catRow->title, 'products' => $catRow->products, 'more' => $catRow->url])
    @endforeach

    @include('LiquorHub.partials.product-row', ['id' => 'hot-deals', 'title' => 'Hot deals', 'products' => $rows['hot'], 'more' => isset($shop) ? $shop['dealsUrl'] : null, 'endsAt' => $shop['endsAt'] ?? null])

    @include('LiquorHub.partials.banners', ['images' => $banners[1], 'class' => 'static_two'])

    @include('LiquorHub.partials.product-row', ['title' => 'Everyday products', 'products' => $rows['everyday']])

    @foreach (collect($shop['categoryRows'] ?? [])->slice(2) as $catRow)
        @include('LiquorHub.partials.product-row', ['title' => $catRow->title, 'products' => $catRow->products, 'more' => $catRow->url])
    @endforeach

    @include('LiquorHub.partials.banners', ['images' => $banners[2], 'class' => 'static_one'])

    @include('LiquorHub.partials.leaflets', ['images' => $leaflets[3]])

    @include('LiquorHub.partials.banners', ['images' => $banners[3], 'class' => 'static_three'])

</main>

@endsection
