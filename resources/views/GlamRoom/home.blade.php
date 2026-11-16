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

    {{-- rows, leaflets and banners woven into one feed --}}
    @include('shop.home-feed', ['ns' => 'GlamRoom'])

</main>

@endsection
