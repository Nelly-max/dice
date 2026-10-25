<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="exRTWF_12EfqA6ZLpUHcFHaljs2Ui_nVAW66gtDWkGI">
    <meta name="customer-auth" content='@json(["authenticated" => auth("customer")->check(), "id" => auth("customer")->id()])'>
    <title>SMART MARKET || Home Market</title>

    {{-- Order matters: shared files first, then homemrkt.css (it replaces ~85 older duplicate
         rules in reused.css), then tokens.css which sets the subdivision colours. --}}
    @vite([
        'resources/css/reused.css',
        'resources/css/modal.css',
        'resources/css/homemrkt.css',
        'resources/css/tokens.css',
        'resources/css/homemarket-ui.css',
        'resources/css/components.css',
        'resources/css/responsive.css',
        'resources/js/softnav.js',
        'resources/js/reused.js',
        'resources/js/modal.js',
        'resources/js/cart.js',
        'resources/js/map.js',
        'resources/js/hmsearch.js',
    ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800&family=DM+Sans:wght@400;500;600;700&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('img/favicon.webp') }}">

    <!-- Remix icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet"/>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body data-subdivision="home-market">
    @include('partials.theme-init')

    @include('HomeMarket.partials.nav')

    @yield('content')

    @include('HomeMarket.partials.footer')

    {{-- Phone only: floating cart bar (hidden while the cart is empty; cart.js toggles [data-cart-bar]) --}}
    @php $barCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity'); @endphp
    <a href="{{ route('cart.index') }}" class="hm-floatbar" data-cart-bar @if ($barCount < 1) hidden @endif>
        <span><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> View cart</span>
        <b class="cart-count" style="display:flex">{{ $barCount }}</b>
    </a>

    {{-- Shared across subdivisions --}}
    @include('partials.switch-shop-sheet')
    @include('modals.map')

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places"
        async
        defer>
    </script>

</body>
</html>
