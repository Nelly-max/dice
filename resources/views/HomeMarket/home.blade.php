@extends('HomeMarket.layouts.app')

@section('content')

<main class="wrapper">

    <!-- <div class="del-options"> ... (unchanged, commented out) ... </div> -->

    <div class="slider">
        <div class="list">
            @php
                $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/Sliders';
                $mediaBaseUrl = rtrim(config('app.media_url'), '/');
                $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];
            @endphp

            @forelse($foundImages as $filePath)
                @php
                    $fileName = basename($filePath);
                @endphp

                <div class="item">
                    <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/Sliders/{{ rawurlencode($fileName) }}" alt="{{ pathinfo($fileName, PATHINFO_FILENAME) }}">
                </div>
            @empty
                <div class="item">
                    <p style="padding: 20px; text-align: center; color: #888;">No hero banner image assets discovered in folder template path.</p>
                </div>
            @endforelse
        </div>

        <div class="buttons">
            <button id="prev"><</button>
            <button id="next">></button>
        </div>
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

    @php
        $categoryBaseUrl = rtrim(config('app.media_url'), '/');
    @endphp

    <section class="category-icons main-cat-icons">

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/hot_sale.png" alt="Hot Sales">
            <span>Hot Sales</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/discount_banner_1.png" alt="Online Exclusive">
            <span>Online Exclusive</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/FR001_HM.png" alt="Foods">
            <span>Foods</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/FD001_HM.png" alt="House Hold">
            <span>House Hold</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/FD002_HM.png" alt="Detergent">
            <span>Detergent</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/FC001_HM.png" alt="Confectionery">
            <span>Confectionery</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/FDR001_HM.png" alt="Dairies">
            <span>Dairies</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/FB001_HM.png" alt="Beverages">
            <span>Beverages</span>
        </a>

        <a href="#" class="category-icon">
            <img src="{{ $categoryBaseUrl }}/media/img/homeMarket/Category_icons/FH001_HM.png" alt="Hygene">
            <span>Hygene</span>
        </a>

    </section>

    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">

            @php
                $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/Carousel_1';
                $mediaBaseUrl = rtrim(config('app.media_url'), '/');
                $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];
                natsort($foundImages);
            @endphp

            @forelse($foundImages as $filePath)
                @php
                    $fileName = basename($filePath);
                    $imageName = pathinfo($fileName, PATHINFO_FILENAME);
                @endphp

                <li class="card">
                    <div class="img">
                        <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/Carousel_1/{{ rawurlencode($fileName) }}" alt="{{ $imageName }}" draggable="false">
                    </div>
                </li>
            @empty
                <li class="card">
                    <div class="img">
                        <p style="padding: 20px; text-align: center; color: #888;">No slider images found.</p>
                    </div>
                </li>
            @endforelse

        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>

    <div class="cards-slider">
        <div class="head">
            <h3 class="sub-heading">Hot Deals</h3>
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

            @php
                $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/Carousel_2';
                $mediaBaseUrl = rtrim(config('app.media_url'), '/');
                $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];
                natsort($foundImages);
            @endphp

            @forelse($foundImages as $filePath)
                @php
                    $fileName = basename($filePath);
                    $imageName = pathinfo($fileName, PATHINFO_FILENAME);
                @endphp

                <li class="card">
                    <div class="img">
                        <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/Carousel_2/{{ rawurlencode($fileName) }}" alt="{{ $imageName }}" draggable="false">
                    </div>
                </li>
            @empty
                <li class="card">
                    <div class="img">
                        <p style="padding: 20px; text-align: center; color: #888;">No slider images found.</p>
                    </div>
                </li>
            @endforelse

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

    <div class="cards-slider">
        <div class="head">
            <h3 class="sub-heading"></h3>
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('HomeMarket.products.items')
        </div>
    </div>

    <section class="static-banners static_two">

        @php
            $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/StaticBanners_1';
            $mediaBaseUrl = rtrim(config('app.media_url'), '/');
            $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
            $foundBanners = glob($imagePattern, GLOB_BRACE) ?: [];
            natsort($foundBanners);
        @endphp

        @forelse($foundBanners as $filePath)
            @php
                $fileName = basename($filePath);
                $imageName = pathinfo($fileName, PATHINFO_FILENAME);
            @endphp

            <div class="static-banner">
                <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/StaticBanners_1/{{ rawurlencode($fileName) }}" alt="{{ $imageName }}">
            </div>
        @empty
            <div class="static-banner">
                <p style="padding: 20px; text-align: center; color: #888;">No static banner images found.</p>
            </div>
        @endforelse

    </section>

    <div class="cards-slider">
        <div class="head">
            <h3 class="sub-heading">House Care & Hygine</h3>
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('HomeMarket.products.items')
        </div>
    </div>

    <section class="static-banners static_one">

        @php
            $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/StaticBanners_2';
            $mediaBaseUrl = rtrim(config('app.media_url'), '/');
            $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
            $foundBanners = glob($imagePattern, GLOB_BRACE) ?: [];
            natsort($foundBanners);
        @endphp

        @forelse($foundBanners as $filePath)
            @php
                $fileName = basename($filePath);
                $imageName = pathinfo($fileName, PATHINFO_FILENAME);
            @endphp

            <div class="static-banner">
                <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/StaticBanners_2/{{ rawurlencode($fileName) }}" alt="{{ $imageName }}">
            </div>
        @empty
            <div class="static-banner">
                <p style="padding: 20px; text-align: center; color: #888;">No static banner images found.</p>
            </div>
        @endforelse

    </section>

    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">

            @php
                $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/Carousel_3';
                $mediaBaseUrl = rtrim(config('app.media_url'), '/');
                $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];
                natsort($foundImages);
            @endphp

            @forelse($foundImages as $filePath)
                @php
                    $fileName = basename($filePath);
                    $imageName = pathinfo($fileName, PATHINFO_FILENAME);
                @endphp

                <li class="card">
                    <div class="img">
                        <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/Carousel_3/{{ rawurlencode($fileName) }}" alt="{{ $imageName }}" draggable="false">
                    </div>
                </li>
            @empty
                <li class="card">
                    <div class="img">
                        <p style="padding: 20px; text-align: center; color: #888;">No slider images found.</p>
                    </div>
                </li>
            @endforelse

        </ul>
        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>

    <div class="items-slider">
        <div class="head">
            <h3 class="sub-heading">Every Day Products</h3>
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('HomeMarket.products.items')
        </div>
    </div>

    <div class="items-slider">
        <div class="head">
            <span>
                <i class="fa-solid fa-angle-left"></i>
                <i class="fa-solid fa-angle-right"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('HomeMarket.products.items')
        </div>
    </div>

    <section class="static-banners static_three">

        @php
            $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/StaticBanners_3';
            $mediaBaseUrl = rtrim(config('app.media_url'), '/');
            $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
            $foundBanners = glob($imagePattern, GLOB_BRACE) ?: [];
            natsort($foundBanners);
        @endphp

        @forelse($foundBanners as $filePath)
            @php
                $fileName = basename($filePath);
                $imageName = pathinfo($fileName, PATHINFO_FILENAME);
            @endphp

            <div class="static-banner">
                <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/StaticBanners_3/{{ rawurlencode($fileName) }}" alt="{{ $imageName }}">
            </div>
        @empty
            <div class="static-banner">
                <p style="padding: 20px; text-align: center; color: #888;">No static banner images found.</p>
            </div>
        @endforelse

    </section>

</main>

@endsection

@include('modals.map')
@include('HomeMarket.partials.search-box')