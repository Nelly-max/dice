@extends('CookingGas.layouts.app')

@section('content')

<main class="wrapper">
    <div class="slider">
        <div class="list">
            <div class="item">
                <img src="../public/img/homeMarket/Hero_Banner_1.jpg" alt="">
            </div>
            <div class="item">
                <img src="../public/img/homeMarket/Hero_Banner_2.jpg" alt="">
            </div>
            <div class="item">
                <img src="../public/img/homeMarket/Hero_Banner_3.jpg" alt="">
            </div>
            <div class="item">
                <img src="../public/img/homeMarket/Hero_Banner_1.jpg" alt="">
            </div>
            <div class="item">
                <img src="../public/img/homeMarket/Hero_Banner_2.jpg" alt="">
            </div>
        </div>

        <!-- button prev and next -->
        <div class="buttons">
            <button id="prev"><</button>
            <button id="next">></button>
        </div>
        <!-- dots (if 5 items =>5 dot) -->
        <ul class="dots">
            <li class="active"></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>
    <div class="cards-slider">
        <div class="head">
            <h3 class="sub-heading">Top Brands</h3>
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>
        <div class="products-container cards-container column-cards row-card" id="searchResults">
            @include('CookingGas.Products.cylinders')
        </div>

        <!-- <div class="products-container column-cards" id="searchResults">
            @include('CookingGas.Products.cylinders')
        </div> -->
    </div>


    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">
            <li class="card">
                <div class="img"><img src="../public/img/homeMarket/LEAFLET_CARD_1.jpg" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="../public/img/homeMarket/LEAFLET_CARD_2.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="../public/img/homeMarket/LEAFLET_CARD_3.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="../public/img/homeMarket/LEAFLET_CARD_4.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="../public/img/homeMarket/LEAFLET_CARD_5.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="../public/img/homeMarket/LEAFLET_CARD_6.png" alt="" draggable="false"></div>
            </li>
        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>
</main>

@endsection
