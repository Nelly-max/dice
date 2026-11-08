<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $subdivision->name }} · Smart Market</title>
    <meta name="description" content="{{ $subdivision->name }} is coming soon to Smart Market.">

    {{--
        Shown by SubdivisionsController@handleWeblink for a service that has no pages of its own yet
        (Airbnb, Water, ...). Same shell as Home Market (homemarket-ui.css); colours are the green
        default from tokens.css. Services with their own routes never reach this page.
        Add a service's text to $services below.
    --}}
    @vite([
        'resources/css/reused.css',
        'resources/css/modal.css',
        'resources/css/homemrkt.css',
        'resources/css/tokens.css',
        'resources/css/homemarket-ui.css',
        'resources/css/components.css',
        'resources/css/responsive.css',
        'resources/css/services-ui.css',
        'resources/css/quick-switch.css',
        'resources/js/softnav.js',
        'resources/js/reused.js',
        'resources/js/modal.js',
        'resources/js/quick-switch.js',
    ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('img/favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
@php
    $key = \Illuminate\Support\Str::lower($subdivision->weblink . ' ' . $subdivision->name);

    $services = [
        'airbnb' => [
            'icon' => 'fa-solid fa-bed',
            'title' => 'Short stays near you',
            'text' => 'Book furnished apartments and rooms by the night or the week, with photos, prices and the host’s contacts in one place.',
            'points' => [
                ['fa-solid fa-calendar-check', '#1d63d8', 'Book by the night', 'Full price shown upfront'],
                ['fa-solid fa-images', '#00c2d1', 'Real photos', 'Every room before you book'],
                ['fa-solid fa-mobile-screen-button', '#00d26a', 'Pay with M-Pesa', 'Confirmed instantly'],
            ],
        ],
        'water' => [
            'icon' => 'fa-solid fa-droplet',
            'title' => 'Clean water, delivered',
            'text' => 'Order bottled water and dispenser refills from suppliers near you, and keep track of your water bills in one place.',
            'points' => [
                ['fa-solid fa-bottle-water', '#1d63d8', 'Refills & bottles', '5, 10 and 20 litres'],
                ['fa-solid fa-motorcycle', '#00c2d1', 'Quick delivery', 'From water shops near you'],
                ['fa-solid fa-file-invoice', '#00d26a', 'Water bills', 'See and pay with M-Pesa'],
            ],
        ],
    ];

    $service = collect($services)->first(fn ($s, $slug) => str_contains($key, $slug)) ?? [
        'icon' => 'fa-solid fa-store',
        'title' => 'Coming soon',
        'text' => 'We are getting ' . $subdivision->name . ' ready. It will open on Smart Market soon.',
        'points' => [],
    ];

    $logo = !empty($subdivision->logo) ? rtrim(config('app.media_url'), '/') . '/' . ltrim($subdivision->logo, '/') : null;
    $customer = auth('customer')->user();
    $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');

    $open = [
        ['Home Market', 'homemarket.home', 'img/logo/homemarket.png'],
        ['Cooking Gas', 'gas.products.index', 'img/logo/cookingGas.png'],
        ['Home City', 'homecity.index', 'img/logo/homecity.png'],
        ['Liquor Hub', 'liquorhub.home', 'img/logo/cheers.png'],
    ];
@endphp
{{-- "soon": styled by homemarket-ui.css, green default colours from tokens.css --}}
<body data-subdivision="soon">
    @include('partials.theme-init')

    <header class="hm-header">
        <div class="hm-bar">
            <a href="{{ route('home') }}" class="hm-logo" aria-label="Smart Market home">
                @if ($logo)<img src="{{ $logo }}" alt="">@endif
                <span>{{ \Illuminate\Support\Str::upper($subdivision->name) }}</span>
            </a>

            <div class="hm-search hc-search" aria-hidden="true">
                <input type="search" placeholder="Search opens when {{ $subdivision->name }} launches" disabled tabindex="-1">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </div>

            <a href="{{ $customer ? route('hub.index') : route('login') }}" @class(['hm-account', 'hm-account--guest' => !$customer])
               aria-label="{{ $customer ? 'My account' : 'Login or register' }}">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                @if ($customer)
                    <span class="hm-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($customer->name ?? 'U'), 0, 1)) }}</span>
                    <span class="hm-account-full">{{ \Illuminate\Support\Str::of($customer->name ?? '')->explode(' ')->first() }}</span>
                @else
                    <span class="hm-account-full">Login &amp; Register</span>
                    <span class="hm-account-short">Login</span>
                @endif
            </a>

            <a href="{{ route('cart.index') }}" class="hm-cart" aria-label="My cart">
                <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
                <span class="hm-cart-label">Cart</span>
                <b class="cart-count" @style(['display: none' => $cartCount < 1])>{{ $cartCount }}</b>
            </a>
        </div>

        <nav class="hm-categories" aria-label="Smart Market">
            <div class="hm-categories-inner">
                <a href="{{ route('home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Smart Market</a>
                <a href="{{ route('home') }}#services">All services</a>
                @foreach ($open as [$name, $route])
                    <a href="{{ route($route) }}">{{ $name }}</a>
                @endforeach
            </div>
        </nav>
    </header>

    <main class="wrapper">

        <section class="soon-hero" aria-labelledby="soon-title">
            <span class="soon-mark" aria-hidden="true">
                @if ($logo)<img src="{{ $logo }}" alt="">@else<i class="{{ $service['icon'] }}"></i>@endif
            </span>
            <div>
                <span class="soon-chip"><i class="fa-regular fa-clock" aria-hidden="true"></i> Coming soon</span>
                <h1 id="soon-title">{{ $subdivision->name }}</h1>
                <h2>{{ $service['title'] }}</h2>
                <p>{{ $service['text'] }}</p>
            </div>
            <i class="{{ $service['icon'] }} soon-art" aria-hidden="true"></i>
        </section>

        @if (!empty($service['points']))
            <div class="info-bar">
                @foreach ($service['points'] as [$icon, $color, $head, $body])
                    <div class="info">
                        <i class="{{ $icon }}" style="--icon: {{ $color }}" aria-hidden="true"></i>
                        <div class="info-txt">
                            <h3>{{ $head }}</h3>
                            <h4>{{ $body }}</h4>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="hm-section-head">
            <h2>Open now on Smart Market</h2>
        </div>
        <section class="category-icons main-cat-icons" aria-label="Open services">
            @foreach ($open as [$name, $route, $img])
                <a href="{{ route($route) }}" class="category-icon">
                    <img src="{{ asset($img) }}" alt="" loading="lazy">
                    <span>{{ $name }}</span>
                </a>
            @endforeach
        </section>

    </main>

    @include('partials.shop-footer', ['name' => $subdivision->name])
    {{-- Floating quick switch: other subdivisions, cart, account, settings --}}
    @include('partials.quick-switch', ['qsHasMap' => false])

</body>
</html>
