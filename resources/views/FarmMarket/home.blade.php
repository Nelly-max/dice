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

    {{-- rows, leaflets and banners woven into one feed --}}
    @include('shop.home-feed', ['ns' => 'FarmMarket'])

</main>

@endsection
