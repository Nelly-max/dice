@extends('HomeCity.layouts.app')

@section('content')

    {{-- Slider --}}
    <div class="slider">
        <div class="list">
            <div class="item"><img src="{{ asset('img/homeCity/Hero_Banner_1.png') }}" alt="Hero Banner 1"></div>
            <div class="item"><img src="{{ asset('img/homeMarket/Hero_Banner_2.jpg') }}" alt="Hero Banner 2"></div>
            <div class="item"><img src="{{ asset('img/homeMarket/Hero_Banner_3.jpg') }}" alt="Hero Banner 3"></div>
        </div>

        <div class="buttons">
            <button id="prev">&lt;</button>
            <button id="next">&gt;</button>
        </div>

        <ul class="dots">
            <li class="active"></li>
            <li></li>
            <li></li>
        </ul>
    </div>

    {{-- Properties --}}
    <div class="cards-slider">
        <div class="head">
            <h3 class="sub-heading">Top Listings</h3>
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>

        <div class="main properties-container cards-container column-cards">
            @foreach($listings as $listing)
                @include('HomeCity.Properties.property-card', ['property' => $listing])
            @endforeach
        </div>
    </div>

    <!-- <div class="main properties-container">
        @foreach($listings as $listing)
            @include('HomeCity.Properties.property-card', ['property' => $listing])
        @endforeach
    </div> -->

    {{-- Carousel --}}
    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">
            <li class="card"><div class="img"><img src="{{ asset('img/homeMarket/LEAFLET_CARD_1.jpg') }}" alt="Leaflet Card 1" draggable="false"></div></li>
            <li class="card"><div class="img"><img src="{{ asset('img/homeMarket/LEAFLET_CARD_2.png') }}" alt="Leaflet Card 2" draggable="false"></div></li>
            <li class="card"><div class="img"><img src="{{ asset('img/homeMarket/LEAFLET_CARD_3.png') }}" alt="Leaflet Card 3" draggable="false"></div></li>
        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>

@endsection

