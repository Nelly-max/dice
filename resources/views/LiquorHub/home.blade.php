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

    {{-- rows, leaflets and banners woven into one feed --}}
    @include('shop.home-feed', ['ns' => 'LiquorHub'])

</main>

@endsection
