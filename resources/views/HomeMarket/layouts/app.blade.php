<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google-site-verification" content="exRTWF_12EfqA6ZLpUHcFHaljs2Ui_nVAW66gtDWkGI" />
    <meta
        name="customer-auth"
        content='@json([
            "authenticated" => auth("customer")->check(),
            "id" => auth("customer")->id(),
        ])'
    >
    <title>SMART MARKET || Home Market</title>

    <!-- Vite CSS + JS -->
    @vite([
        'resources/css/homemrkt.css',
        'resources/css/reused.css',
        'resources/css/tokens.css',
        'resources/css/homemarket-ui.css',
        'resources/css/product-cards.css',
        'resources/css/components.css',
        'resources/css/smart-home.css',
        'resources/js/reused.js',
        'resources/js/softnav.js',
        'resources/js/hmsearch.js',
        'resources/js/cart.js',
        'resources/js/checkout.js',
        'resources/js/orderPayment.js',
        'resources/js/map.js',
    ])

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- Remix icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet"/>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Google Fonts for tokens.css -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&family=Bricolage+Grotesque:wght@400;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body data-subdivision="home-market">

    {{-- Navbar --}}
    @include('HomeMarket.partials.nav')

    {{-- Main Content --}}
    @yield('content')

    {{-- Footer --}}
    @include('HomeMarket.partials.footer')

</body>
</html>
