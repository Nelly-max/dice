@php
    $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
    $customer = auth('customer')->user();
    $radiusKm = config('water.max_radius_m', 3000) / 1000;
    $wtLogo = (($subdivisionName ?? 'Marketplace') !== 'Marketplace' && !empty($logoUrl)) ? $logoUrl : asset('img/logo/water.png');
    $wtHome = route('water.index');
    $wtOptions = config('water.options', []);
@endphp

<header class="hm-header">
    <div class="hm-bar">

        <a href="{{ $wtHome }}" class="hm-logo" aria-label="Water home">
            <img src="{{ $wtLogo }}" alt="">
            <span>WATER</span>
        </a>

        <form class="hm-search hc-search" action="{{ $wtHome }}" method="get" role="search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search refills, bottled water, water trucks…" aria-label="Search water" autocomplete="off" enterkeyhint="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        </form>

        <button type="button" class="hm-deliver" onclick="showModal('mapModal')">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <span>
                <small>Deliver to</small>
                <strong id="current-delivery-location">{{ $customer->delivery_address ?? 'Select delivery location' }}</strong>
                <em class="hm-radius">· {{ $radiusKm }} km</em>
            </span>
            <i class="fa-solid fa-chevron-down hm-deliver-caret" aria-hidden="true"></i>
        </button>

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

    <nav class="hm-categories" aria-label="Water categories">
        <div class="hm-categories-inner">
            <a href="{{ $wtHome }}" @class(['active' => request()->routeIs('water.index') && !request()->filled('q')])><i class="ri-drop-line" aria-hidden="true"></i> Home</a>
            @foreach ($wtOptions as $key => $opt)
                <a href="{{ route($opt['route']) }}" @class(['active' => request()->routeIs($opt['route'])])>{{ $opt['label'] }}</a>
            @endforeach
            <a href="{{ route('water.shops') }}" @class(['active' => request()->routeIs('water.shops', 'water.shop')])>Shops near you</a>
        </div>
    </nav>
</header>
