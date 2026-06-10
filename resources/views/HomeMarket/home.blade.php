@extends('HomeMarket.layouts.app')

@section('content')

<main class="wrapper">
    <div class="del-options">
        <div class="del-option">
            <div class="opt-icon">
                <i class="ri-truck-line"></i>
                <span>Scheduled</span>
            </div>
            <div class="text-del">
                <div class="detail">
                    <h3>Delivery</h3>
                    <h4>Next Day</h4>
                </div>
                <div class="detail">
                    <h3>Minimum Order</h3>
                    <h4>KES 100+</h4>
                </div>
                <i class="fa-solid fa-circle-info del-info-icon"></i>
            </div>
        </div>
        <div class="del-option active">
            <div class="opt-icon">
                <i class="ri-e-bike-2-line"></i>
                <span>Quick shop</span>
            </div>
            <div class="text-del">
                <div class="detail">
                    <h3>Delivery Time</h3>
                    <h4>in 50 min</h4>
                </div>
                <div class="detail">
                    <h3>Minimum Order</h3>
                    <h4>KES 500+</h4>
                </div>
                <i class="fa-solid fa-circle-info del-info-icon"></i>
            </div>
        </div>
    </div>
    <div class="slider">
        <div class="list">
            @php
                // 1. Establish the absolute local file system access route path on your Windows machine
                $localPath = 'C:\media\img\homeMarket\Sliders';

                // 2. Safely resolve your application's absolute public network domain routing endpoint address
                $mediaBaseUrl = rtrim(config('app.media_url') ?: env('MEDIA_URL'), '/');

                // 3. Scan the storage index layout directory structure to find matching image file extension formats
                // This looks up files matching .jpg, .jpeg, .png, and .webp patterns
                $imagePattern = $localPath . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];
            @endphp

            @forelse($foundImages as $filePath)
                @php
                    // 4. Extract just the trailing filename string part out of the absolute storage path mapping text
                    $fileName = basename($filePath);
                @endphp
                
                <div class="item">
                    <!-- 5. Generate absolute programmatic client URLs targeting your application media server link path -->
                    <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/Sliders/{{ $fileName }}" alt="{{ pathinfo($fileName, PATHINFO_FILENAME) }}">
                </div>
            @empty
                <!-- Fallback block configuration to prevent design breaks if the directory index layout returns empty -->
                <div class="item">
                    <p style="padding: 20px; text-align: center; color: #888;">No hero banner image assets discovered in folder template path.</p>
                </div>
            @endforelse
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
    <section class="category-icons main-cat-icons">
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/hot_sale.png" alt="">
            <span>Hot Sales</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/discount_banner_1.png" alt="">
            <span>Online Exclusive</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/FR001_HM.png" alt="">
            <span>Foods</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/FD001_HM.png" alt="">
            <span>House Hold</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/FD002_HM.png" alt="">
            <span>Detergent</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/FC001_HM.png" alt="">
            <span>Confectionery</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/FDR001_HM.png" alt="">
            <span>Dairies</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/FB001_HM.png" alt="">
            <span>Beverages</span>
        </a>
        <a href="#" class="category-icon">
            <img src="/public/img/homeMarket/FH001_HM.png" alt="">
            <span>Hygene</span>
        </a>
    </section>
    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_1.jpg" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_2.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_3.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_4.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_5.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_6.png" alt="" draggable="false"></div>
            </li>
        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>
    <div class="cards-slider">
        <div class="head">
            <h3 class="sub-heading">Most Popular</h3>
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('HomeMarket.products.items')
        </div>
    </div>
    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_1.jpg" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_2.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_3.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_4.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_5.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_6.png" alt="" draggable="false"></div>
            </li>
        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>

    <section class="static-banners static_two">
        <div class="static-banner">
            <img src="/public/img/homeMarket/STATIC_BANNER_1.jpg" alt="">
        </div>
        <div class="static-banner">
            <img src="/public/img/homeMarket/STATIC_BANNER_2.jpg" alt="">
        </div>
    </section>
    <div class="items-slider">
        <h3 class="heading">Big Deals</h3>
        <div class="items-container column-items">
            <div class="item-container active" data-content="10%">
                <a href="#">
                    <img src="/public/img/homeMarket/FH001_HM.png" alt="">
                </a>
                <span>
                    <div class="price">
                        <h4>Ksh</h4>
                        <h4 class="cash">410.00</h4>
                    </div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </span>
                <h4>Jik clothes bleach, jik companies. only white</h4>
            </div>
            <div class="item-container">
                <a href="#">
                    <img src="/public/img/homeMarket/FD001_HM.png" alt="">
                </a>
                <span>
                    <div class="price">
                        <h4>Ksh</h4>
                        <h4 class="cash">175.00</h4>
                    </div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </span>
                <h4>Geisha</h4>
            </div>
        </div>
    </div>
    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_1.jpg" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_2.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_3.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_4.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_5.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_6.png" alt="" draggable="false"></div>
            </li>
        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>
    <div class="items-slider">
        <div class="head">
            <h3 class="sub-heading">Most Popular</h3>
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>
        <div class="items-container column-items">
            <div class="item-container">
                <a href="#">
                    <img src="/public/img/homeMarket/FR001_HM.png" alt="">
                </a>
                <span>
                    <div class="price">
                        <h4>Ksh</h4>
                        <h4 class="cash">370.00</h4>
                    </div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </span>
                <h5 data-discount="10%Off">Ksh 480</h5>
                <h4>Rina vegetable cooking oil from kapa oil refinaries</h4>
            </div>
            <div class="item-container active" data-content="10%">
                <a href="#">
                    <img src="/public/img/homeMarket/FH001_HM.png" alt="">
                </a>
                <span>
                    <div class="price">
                        <h4>Ksh</h4>
                        <h4 class="cash">410.00</h4>
                    </div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </span>
                <h4>Jik clothes bleach, jik companies. only white</h4>
            </div>
            <div class="item-container">
                <a href="#">
                    <img src="/public/img/homeMarket/FC001_HM.png" alt="">
                </a>
                <span>
                    <div class="price">
                        <h4>Ksh</h4>
                        <h4 class="cash">370.00</h4>
                    </div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </span>
                <h4>Dairy milk chocolate</h4>
            </div>
        </div>
    </div>
    <section class="static-banners static_one">
        <div class="static-banner">
            <img src="/public/img/homeMarket/STATIC_BANNER_1.jpg" alt="">
        </div>
    </section>
    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_1.jpg" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_2.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_3.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_4.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_5.png" alt="" draggable="false"></div>
            </li>
            <li class="card">
                <div class="img"><img src="/public/img/homeMarket/LEAFLET_CARD_6.png" alt="" draggable="false"></div>
            </li>
        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>
    <div class="items-slider">
        <h3 class="sub-heading">For your kitchen</h3>
        <div class="items-container column-items">
            <div class="item-container">
                <a href="#">
                    <img src="/public/img/homeMarket/FR001_HM.png" alt="">
                </a>
                <span>
                    <div class="price">
                        <h4>Ksh</h4>
                        <h4 class="cash">370.00</h4>
                    </div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </span>
                <h5 data-discount="10%Off">Ksh 480</h5>
                <h4>Rina vegetable cooking oil from kapa oil refinaries</h4>
            </div>
            <div class="item-container active" data-content="10%">
                <a href="#">
                    <img src="/public/img/homeMarket/FH001_HM.png" alt="">
                </a>
                <span>
                    <div class="price">
                        <h4>Ksh</h4>
                        <h4 class="cash">410.00</h4>
                    </div>
                    <i class="fa-solid fa-basket-shopping"></i>
                </span>
                <h4>Jik clothes bleach, jik companies. only white</h4>
            </div>
        </div>
    </div>
    <section class="static-banners static_three">
        <div class="static-banner">
            <img src="/public/img/homeMarket/STATIC_BANNER_1.jpg" alt="">
        </div>
        <div class="static-banner">
            <img src="/public/img/homeMarket/STATIC_BANNER_2.jpg" alt="">
        </div>
        <div class="static-banner">
            <img src="/public/img/homeMarket/STATIC_BANNER_2.jpg" alt="">
        </div>
    </section>

</main>

@endsection
