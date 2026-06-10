@extends('layouts.app')

@section('content')

<main  class="wrapper">
    <div class="info-bar-extra">
        Exclusive discounts and many more
    </div>
    <section class="slider">
        <div class="list">
            <div class="item">
                <img src="{{ asset('img/Hero_Banner_1.png') }}" alt="">
            </div>
            <div class="item">
                <img src="{{ asset('img/homeMarket/Hero_Banner_2.jpg') }}" alt="">
            </div>
            <div class="item">
                <img src="{{ asset('img/homeMarket/Hero_Banner_3.jpg') }}" alt="">
            </div>
            <div class="item">
                <img src="{{ asset('img/homeMarket/Hero_Banner_1.jpg') }}" alt="">
            </div>
            <div class="item">
                <img src="{{ asset('img/homeMarket/Hero_Banner_2.jpg') }}" alt="">
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
    </section>

    <div class="info-bar">
        <a href="#" class="info">
            <i class="fa-brands fa-hive" style="color: #ff8c00"></i>
            <div class="info-txt">
                <h3>Followed Stores</h3>
                <h4>save favourite shops</h4>
            </div>
        </a>

        <a href="#" class="info">
            <i class="fa-solid fa-store" style="color: #00ffff"></i>
            <div class="info-txt">
                <h3>Stores Nearby</h3>
                <h4>View Shops Near Me</h4>
            </div>
        </a>

        <a href="#" class="info">
            <i class="fa-solid fa-tags" style="color: #00ff84"></i>
            <div class="info-txt">
                <h3>Weekly Discount</h3>
                <h4>Stay alert of our discounts</h4>
            </div>               
        </a>
    </div>

    <div class="contents">
        <section class="card-icons-holder">
            <div class="card-head">
                <h3 class="sub-heading">Products</h3>
                <a href="products.html" class="show-all">
                    view all
                    <i class="fa-solid fa-arrow-right-long"></i>
                </a>
            </div>
            <div class="card-icons">
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/builders.png') }}" alt="">
                        <h6>Builders Piont</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/autocar2.webp') }}" alt="">
                        <h6>Auto Car</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/autobikeslogo.png') }}" alt="">
                        <h6>Auto Bikes</h6>
                    </a>                            
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/health.png') }}" alt="">
                        <h6>Better Health</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/homemarket.png') }}" alt="">
                        <h6>Home Market</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/farmmarket.png') }}" alt="">
                        <h6>Farm Market</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/cheers.png') }}" alt="">
                        <h6>Cheers Central</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/furniture.jpg') }}" alt="">
                        <h6>Furniture City</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/kitchen.png') }}" alt="">
                        <h6>Home Ware</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/airspace.png') }}" alt="">
                        <h6>Air Space</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/pets logo.png') }}" alt="">
                        <h6>Simba</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/baby.png') }}" alt="">
                        <h6>Lovely Baby</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/beauty.png') }}" alt="">
                        <h6>Alene One</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/knowledge.png') }}" alt="">
                        <h6>Knowledge Stream</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/water.png') }}" alt="">
                        <h6>Water</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/logo/clothes.png') }}" alt="">
                        <h6>Refined Rag</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/admin.png') }}" alt="">
                        <h6>Props & More</h6>
                    </a>
                </div>
                <div class="icon">
                    <a href="#">
                        <img src="{{ asset('img/admin.png') }}" alt="">
                        <h6>Massive</h6>
                    </a>
                </div>
            </div>
        </section>

        <!-- Repeat this pattern for all other <img> tags in other sections -->

    </div>

    <div class="end">
        <!-- rolling dice -->
    </div>
</main> 

@endsection
