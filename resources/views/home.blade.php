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
        @foreach($majorDivisions as $major)
            <section class="card-icons-holder">
                <div class="card-head">
                    <h3 class="sub-heading">{{ $major->name }}</h3>
                    <a href="{{ route('products.byMajorDivision', $major->id) }}" class="show-all">
                        view all
                        <i class="fa-solid fa-arrow-right-long"></i>
                    </a>
                </div>

                <div class="card-icons">
                    @foreach($major->subDivisions as $sub)
                        <div class="icon">
                            {{-- Pass the weblink attribute here --}}
                            <a href="{{ route('subdivision.route', $sub->weblink) }}">
                                <img src="{{ config('app.media_url') . '/' . ltrim($sub->logo, '/') }}" alt="{{ $sub->name }}">
                                <h6>{{ $sub->name }}</h6>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

    </div>


    <div class="end">
        <!-- rolling dice -->
    </div>
</main> 

@endsection
