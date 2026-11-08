@php
    $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
    $customer = auth('customer')->user();
    $radiusKm = config('homemarket.max_radius_m') / 1000;
    // the subdivision logo from the hub (shared by AppServiceProvider), or the bundled one
    $gasLogo = (($subdivisionName ?? 'Marketplace') !== 'Marketplace' && !empty($logoUrl)) ? $logoUrl : asset('img/logo/cookingGas.png');
    // brand links: the home page passes $gasBrands (brand => anchor); other pages link back to it
    $gasHome = route('gas.products.index');
    $onHome = request()->routeIs('gas.products.index');
@endphp

<header class="hm-header">
    <div class="hm-bar">

        <a href="{{ $gasHome }}" class="hm-logo" aria-label="Cooking Gas home">
            <img src="{{ $gasLogo }}" alt="">
            <span>COOKING GAS</span>
        </a>

        {{-- Tapping the field opens the live search panel (hmsearch.js, answered by ProductController@search). --}}
        <div class="hm-search" data-hm-search>
            <input type="search" placeholder="Search gas brands, e.g. K-Gas, Total…" aria-label="Search gas" readonly>
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            @include('CookingGas.partials.search-box')
        </div>

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
            {{-- cart.js updates .cart-count; it stays in the DOM (hidden) when the cart is empty --}}
            <b class="cart-count" @style(['display: none' => $cartCount < 1])>{{ $cartCount }}</b>
        </a>
    </div>

    <nav class="hm-categories" aria-label="Gas brands">
        <div class="hm-categories-inner">
            <a href="{{ $gasHome }}" @class(['active' => $onHome])><i class="ri-fire-line" aria-hidden="true"></i> All gas</a>
            {{-- browse pages (GasPagesController) --}}
            <a href="{{ route('gas.cylinders') }}" @class(['active' => request()->routeIs('gas.cylinders') && !request('brand')])>Gas cylinders</a>
            <a href="{{ route('gas.brands') }}" @class(['active' => request()->routeIs('gas.brands') || (request()->routeIs('gas.cylinders') && request('brand'))])>Brands</a>
            <a href="{{ route('gas.shops') }}" @class(['active' => request()->routeIs('gas.shops', 'gas.shop')])>Shops near you</a>
            <a href="{{ $onHome ? '' : $gasHome }}#best-prices">Best prices</a>
            {{-- brand links (one per brand row on the home page) hidden for now; Brands page covers them --}}
        </div>
    </nav>
</header>
