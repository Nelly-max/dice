@php
    $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
    $customer = auth('customer')->user();
    $radiusKm = config('homemarket.max_radius_m') / 1000;
@endphp

<div class="hm-strip">Shops within {{ $radiusKm }} km of your delivery location</div>

<header class="hm-header">
    <div class="hm-bar">

        <a href="{{ route('homemarket.home') }}" class="hm-logo" aria-label="HomeMarket home">
            <img src="{{ $logoUrl }}" alt="">
            <span>HOME MARKET</span>
        </a>

        {{-- .search-items + its input are read by reused.js (opens the .search-box overlay) --}}
        <div class="hm-search search-items">
            <input type="search" placeholder="Search from shops near you…" aria-label="Search shops near you">
            <i class="ri-search-line" aria-hidden="true"></i>
            @include('HomeMarket.partials.search-box')
        </div>

        <button type="button" class="hm-deliver" onclick="showModal('mapModal')">
            <i class="ri-map-pin-line" aria-hidden="true"></i>
            <span>
                <small>Deliver to</small>
                <strong id="current-delivery-location">{{ $customer->delivery_address ?? 'Select delivery location' }}</strong>
            </span>
        </button>

        <a href="{{ $customer ? route('hub.index') : route('login') }}" @class(['hm-account', 'hm-account--guest' => !$customer])
           aria-label="{{ $customer ? 'My account' : 'Login or register' }}">
            <i class="ri-user-line" aria-hidden="true"></i>
            @if ($customer)
                <span class="hm-account-full">{{ $customer->name }}</span>
            @else
                <span class="hm-account-full">Login &amp; Register</span>
                <span class="hm-account-short">Login</span>
            @endif
        </a>

        <a href="{{ route('cart.index') }}" class="hm-cart" aria-label="My cart">
            <i class="ri-shopping-bag-3-line" aria-hidden="true"></i>
            <span class="hm-cart-label">Cart</span>
            {{-- cart.js updates .cart-count; it stays in the DOM (hidden) when the cart is empty --}}
            <b class="cart-count" @style(['display: none' => $cartCount < 1])>{{ $cartCount }}</b>
        </a>
    </div>

    {{-- Sub-categories only. Edit the list in config/homemarket.php (nav_categories). --}}
    <nav class="hm-categories" aria-label="Categories">
        <div class="hm-categories-inner">
            <a href="{{ route('homemarket.home') }}" @class(['active' => !request()->filled('category')])>All categories</a>

            @foreach (config('homemarket.nav_categories') as $category)
                @if ($category['category_id'])
                    <a href="{{ route('homemarket.home', ['category' => $category['category_id']]) }}"
                       @class(['active' => (int) request('category') === $category['category_id']])>{{ $category['label'] }}</a>
                @else
                    <a href="#">{{ $category['label'] }}</a>
                @endif
            @endforeach
        </div>
    </nav>
</header>
