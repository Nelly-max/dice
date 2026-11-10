@extends('GlamRoom.layouts.app')

@section('content')

<main class="wrapper">

    @include('GlamRoom.partials.hero', ['slides' => $hero])

    {{-- One slot under the hero: "Set your delivery location" (or the cart-shop / out-of-range
         notice) until a location is saved, then the shops near that location take its place. --}}
    @if (!$hasLocation || $outOfRange || $cartShopName)
        @include('GlamRoom.partials.context-banner')
    @else
        @include('GlamRoom.partials.shops-near')
    @endif

    @include('GlamRoom.partials.category-tiles')

    @isset($shop)
        @include('shop.offers-strip')
    @endisset

    @include('GlamRoom.partials.shop-by')

    @include('GlamRoom.partials.product-row', ['title' => 'Fresh in store', 'products' => $rows['fresh']])

    @include('GlamRoom.partials.leaflets', ['images' => $leaflets[1]])

    @include('GlamRoom.partials.leaflets', ['images' => $leaflets[2]])

    {{-- a row for each of the biggest categories, with "See all" (ShopPages::shopHome) --}}
    @foreach (collect($shop['categoryRows'] ?? [])->take(2) as $catRow)
        @include('GlamRoom.partials.product-row', ['title' => $catRow->title, 'products' => $catRow->products, 'more' => $catRow->url])
    @endforeach

    @include('GlamRoom.partials.product-row', ['id' => 'hot-deals', 'title' => 'Hot deals', 'products' => $rows['hot'], 'more' => isset($shop) ? $shop['dealsUrl'] : null, 'endsAt' => $shop['endsAt'] ?? null])

    @include('GlamRoom.partials.banners', ['images' => $banners[1], 'class' => 'static_two'])

    @include('GlamRoom.partials.product-row', ['title' => 'Everyday products', 'products' => $rows['everyday']])

    @foreach (collect($shop['categoryRows'] ?? [])->slice(2) as $catRow)
        @include('GlamRoom.partials.product-row', ['title' => $catRow->title, 'products' => $catRow->products, 'more' => $catRow->url])
    @endforeach

    @include('GlamRoom.partials.banners', ['images' => $banners[2], 'class' => 'static_one'])

    @include('GlamRoom.partials.leaflets', ['images' => $leaflets[3]])

    @include('GlamRoom.partials.banners', ['images' => $banners[3], 'class' => 'static_three'])

</main>

@endsection
