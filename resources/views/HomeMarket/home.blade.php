@extends('HomeMarket.layouts.app')

@section('content')

<main class="wrapper">

    <!-- <div class="del-options">
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
    </div> -->

    <div class="slider">
        <div class="list">
            @php
                // 1. Establish the absolute local file system access route path on the server
                $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/Sliders';

                // 2. Safely resolve your application's absolute public network domain routing endpoint address
                $mediaBaseUrl = rtrim(config('app.media_url'), '/');

                // 3. Scan the storage index layout directory structure to find matching image file extension formats
                // This looks up files matching .jpg, .jpeg, .png, and .webp patterns
                $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];
            @endphp

            @forelse($foundImages as $filePath)
                @php
                    // 4. Extract just the trailing filename string part out of the absolute storage path mapping text
                    $fileName = basename($filePath);
                @endphp

                <div class="item">
                    <!-- 5. Generate absolute programmatic client URLs targeting your application media server link path -->
                    <img src="{{ $mediaBaseUrl }}/media/img/homeMarket/Sliders/{{ rawurlencode($fileName) }}" alt="{{ pathinfo($fileName, PATHINFO_FILENAME) }}">
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
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/hot_sale.png"
                alt="Hot Sales"
            >
            <span>Hot Sales</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/discount_banner_1.png"
                alt="Online Exclusive"
            >
            <span>Online Exclusive</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/FR001_HM.png"
                alt="Foods"
            >
            <span>Foods</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/FD001_HM.png"
                alt="House Hold"
            >
            <span>House Hold</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/FD002_HM.png"
                alt="Detergent"
            >
            <span>Detergent</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/FC001_HM.png"
                alt="Confectionery"
            >
            <span>Confectionery</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/FDR001_HM.png"
                alt="Dairies"
            >
            <span>Dairies</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/FB001_HM.png"
                alt="Beverages"
            >
            <span>Beverages</span>
        </a>


        <a href="#" class="category-icon">
            <img
                src="{{ $mediaBaseUrl }}/media/img/homeMarket/Category_icons/FH001_HM.png"
                alt="Hygene"
            >
            <span>Hygene</span>
        </a>

    </section>

    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>

        <ul class="carousel">

            @php
                // Local folder containing the carousel images
                $localPath = 'C:\media\img\homeMarket\Carousel_1';

                // Media server base URL
                $mediaBaseUrl = rtrim(
                    config('app.media_url') ?: env('MEDIA_URL'),
                    '/'
                );

                // Supported image formats
                $imagePattern = $localPath . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';

                // Find all carousel images
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];

                // Optional: sort alphabetically
                natsort($foundImages);
            @endphp

            @forelse($foundImages as $filePath)

                @php
                    $fileName = basename($filePath);
                    $imageName = pathinfo($fileName, PATHINFO_FILENAME);
                @endphp

                <li class="card">
                    <div class="img">
                        <img
                            src="{{ $mediaBaseUrl }}/media/img/homeMarket/Carousel_1/{{ rawurlencode($fileName) }}"
                            alt="{{ $imageName }}"
                            draggable="false"
                        >
                    </div>
                </li>

            @empty

                <li class="card">
                    <div class="img">
                        <p style="padding: 20px; text-align: center; color: #888;">
                            No slider images found.
                        </p>
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
                // Server-side folder containing the carousel images
                $localPath = rtrim(config('app.media_root'), '/') . '/img/homeMarket/Carousel_2';

                // Media server base URL
                $mediaBaseUrl = rtrim(config('app.media_url'), '/');

                // Supported image formats
                $imagePattern = $localPath . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';

                // Find all carousel images
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];

                // Optional: sort alphabetically
                natsort($foundImages);
            @endphp

            @forelse($foundImages as $filePath)

                @php
                    $fileName = basename($filePath);
                    $imageName = pathinfo($fileName, PATHINFO_FILENAME);
                @endphp

                <li class="card">
                    <div class="img">
                        <img
                            src="{{ $mediaBaseUrl }}/media/img/homeMarket/Carousel_2/{{ rawurlencode($fileName) }}"
                            alt="{{ $imageName }}"
                            draggable="false"
                        >
                    </div>
                </li>

            @empty

                <li class="card">
                    <div class="img">
                        <p style="padding: 20px; text-align: center; color: #888;">
                            No slider images found.
                        </p>
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
            // Local folder containing static banner images
            $localPath = 'C:\media\img\homeMarket\StaticBanners_1';

            // Media server base URL
            $mediaBaseUrl = rtrim(
                config('app.media_url') ?: env('MEDIA_URL'),
                '/'
            );

            // Supported image formats
            $imagePattern = $localPath . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';

            // Find all banner images
            $foundBanners = glob($imagePattern, GLOB_BRACE) ?: [];

            // Keep banners in natural filename order
            natsort($foundBanners);
        @endphp

        @forelse($foundBanners as $filePath)

            @php
                $fileName = basename($filePath);
                $imageName = pathinfo($fileName, PATHINFO_FILENAME);
            @endphp

            <div class="static-banner">
                <img
                    src="{{ $mediaBaseUrl }}/media/img/homeMarket/StaticBanners_1/{{ rawurlencode($fileName) }}"
                    alt="{{ $imageName }}"
                >
            </div>

        @empty

            <div class="static-banner">
                <p style="padding: 20px; text-align: center; color: #888;">
                    No static banner images found.
                </p>
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
            // Local folder containing static banner images
            $localPath = 'C:\media\img\homeMarket\StaticBanners_2';

            // Media server base URL
            $mediaBaseUrl = rtrim(
                config('app.media_url') ?: env('MEDIA_URL'),
                '/'
            );

            // Supported image formats
            $imagePattern = $localPath . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';

            // Find all banner images
            $foundBanners = glob($imagePattern, GLOB_BRACE) ?: [];

            // Keep banners in natural filename order
            natsort($foundBanners);
        @endphp

        @forelse($foundBanners as $filePath)

            @php
                $fileName = basename($filePath);
                $imageName = pathinfo($fileName, PATHINFO_FILENAME);
            @endphp

            <div class="static-banner">
                <img
                    src="{{ $mediaBaseUrl }}/media/img/homeMarket/StaticBanners_2/{{ rawurlencode($fileName) }}"
                    alt="{{ $imageName }}"
                >
            </div>

        @empty

            <div class="static-banner">
                <p style="padding: 20px; text-align: center; color: #888;">
                    No static banner images found.
                </p>
            </div>

        @endforelse

    </section>

    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>
        <ul class="carousel">

            @php
                // Local folder containing the carousel images
                $localPath = 'C:\media\img\homeMarket\Carousel_3';

                // Media server base URL
                $mediaBaseUrl = rtrim(
                    config('app.media_url') ?: env('MEDIA_URL'),
                    '/'
                );

                // Supported image formats
                $imagePattern = $localPath . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';

                // Find all carousel images
                $foundImages = glob($imagePattern, GLOB_BRACE) ?: [];

                // Optional: sort alphabetically
                natsort($foundImages);
            @endphp

            @forelse($foundImages as $filePath)

                @php
                    $fileName = basename($filePath);
                    $imageName = pathinfo($fileName, PATHINFO_FILENAME);
                @endphp

                <li class="card">
                    <div class="img">
                        <img
                            src="{{ $mediaBaseUrl }}/media/img/homeMarket/Carousel_3/{{ rawurlencode($fileName) }}"
                            alt="{{ $imageName }}"
                            draggable="false"
                        >
                    </div>
                </li>

            @empty

                <li class="card">
                    <div class="img">
                        <p style="padding: 20px; text-align: center; color: #888;">
                            No slider images found.
                        </p>
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
            // Local folder containing static banner images
            $localPath = 'C:\media\img\homeMarket\StaticBanners_3';

            // Media server base URL
            $mediaBaseUrl = rtrim(
                config('app.media_url') ?: env('MEDIA_URL'),
                '/'
            );

            // Supported image formats
            $imagePattern = $localPath . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}';

            // Find all banner images
            $foundBanners = glob($imagePattern, GLOB_BRACE) ?: [];

            // Keep banners in natural filename order
            natsort($foundBanners);
        @endphp

        @forelse($foundBanners as $filePath)

            @php
                $fileName = basename($filePath);
                $imageName = pathinfo($fileName, PATHINFO_FILENAME);
            @endphp

            <div class="static-banner">
                <img
                    src="{{ $mediaBaseUrl }}/media/img/homeMarket/StaticBanners_3/{{ rawurlencode($fileName) }}"
                    alt="{{ $imageName }}"
                >
            </div>

        @empty

            <div class="static-banner">
                <p style="padding: 20px; text-align: center; color: #888;">
                    No static banner images found.
                </p>
            </div>

        @endforelse

    </section>

</main>



@endsection

@include('modals.map')
@include('HomeMarket.partials.search-box')

