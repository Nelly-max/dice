<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Market || Cart')</title>

    {{-- Shared by cart, checkout, M-Pesa payment and order success. main.css keeps the old
         structural rules; tokens/components/flow.css (loaded last) give them the new look. --}}
    @vite([
        'resources/css/modal.css',
        'resources/css/reused.css',
        'resources/css/main.css',
        'resources/css/tokens.css',
        'resources/css/components.css',
        'resources/css/flow.css',
        'resources/css/product-cards.css',
            'resources/css/responsive.css',

        'resources/js/softnav.js',
        'resources/js/signup.js',
        'resources/js/reused.js',
        'resources/js/modal.js',
        'resources/js/cart.js',
        'resources/js/checkout.js',
        'resources/js/orderPayment.js',
        'resources/js/map.js',
    ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('img/favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
{{-- $shopping (AppServiceProvider): the subdivision the customer came from, or Smart Market --}}
<body data-subdivision="{{ $shopping['theme'] }}">
    @include('partials.theme-init')

    @php
        $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
    @endphp

    <header class="fl-header">
        <div class="fl-bar">
            {{-- phones: back arrow (the pages set @section('back')) --}}
            <a href="@yield('back', $shopping['homeUrl'])" class="fl-back" aria-label="Back"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>

            <a href="{{ $shopping['homeUrl'] }}" class="fl-logo" aria-label="{{ $shopping['name'] }} home">
                <img src="{{ $shopping['logo'] }}" alt="">
                <span>{{ $shopping['name'] }}</span>
            </a>

            <div class="fl-title">
                <strong>@yield('heading', 'Cart')</strong>
                @hasSection('meta')<small>@yield('meta')</small>@endif
            </div>

            <a href="{{ $shopping['homeUrl'] }}" class="fl-continue">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Continue shopping</span>
            </a>

            <a href="{{ route('cart.index') }}" class="fl-cart" aria-label="My cart">
                <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                <b class="cart-count" @style(['display: none' => $cartCount < 1])>{{ $cartCount }}</b>
            </a>
        </div>
    </header>

    <main class="fl-main">
        @yield('content')
    </main>

    {{-- footer of the subdivision the customer came from (Smart Market's own on the main site) --}}
    @include($shopping['footer'], ['name' => $shopping['name']])

    @include('partials.switch-shop-sheet')

    {{-- pages can add sheets here, e.g. the delivery location sheet on checkout --}}
    @stack('sheets')

</body>
</html>
