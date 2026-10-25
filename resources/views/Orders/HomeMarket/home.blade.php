@extends('HomeMarket.layouts.app')

@section('content')

<main class="wrapper">

    @include('HomeMarket.partials.context-banner')

    @include('HomeMarket.partials.hero', ['slides' => $hero])

    @include('HomeMarket.partials.info-bar')

    @include('HomeMarket.partials.category-tiles')

    @include('HomeMarket.partials.leaflets', ['images' => $leaflets[1]])

    @include('HomeMarket.partials.product-row', ['id' => 'hot-deals', 'title' => 'Hot deals', 'products' => $rows['hot']])

    @include('HomeMarket.partials.leaflets', ['images' => $leaflets[2]])

    @include('HomeMarket.partials.product-row', ['title' => 'Fresh in store', 'products' => $rows['fresh']])

    @include('HomeMarket.partials.banners', ['images' => $banners[1], 'class' => 'static_two'])

    @include('HomeMarket.partials.product-row', ['title' => 'Everyday products', 'products' => $rows['everyday']])

    @include('HomeMarket.partials.banners', ['images' => $banners[2], 'class' => 'static_one'])

    @include('HomeMarket.partials.leaflets', ['images' => $leaflets[3]])

    @include('HomeMarket.partials.banners', ['images' => $banners[3], 'class' => 'static_three'])

</main>

@endsection
