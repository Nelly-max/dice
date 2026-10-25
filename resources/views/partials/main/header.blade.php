{{-- Main-site header (home, settings, information pages). layouts/main includes it. --}}
@php
    $customer = auth('customer')->user();
    $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
    $firstName = $customer ? \Illuminate\Support\Str::of($customer->name ?? '')->explode(' ')->first() : null;
@endphp

<header class="sm-header">
    <div class="sm-bar">
        <a href="{{ route('home') }}" class="sm-logo" aria-label="Smart Market home">
            <span class="sm-logo-mark" aria-hidden="true">S</span>
            <span>Smart Market</span>
        </a>

        <nav class="sm-links" aria-label="Main">
            <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Home</a>
            <a href="{{ route('products') }}" @class(['active' => request()->routeIs('products')])>Products</a>
            <a href="{{ route('page', 'help') }}" @class(['active' => request()->is('help')])>Help</a>
            <a href="{{ route('page', 'about') }}" @class(['active' => request()->is('about')])>About</a>
        </nav>

        <div class="sm-actions">
            <a href="{{ route('setting') }}" @class(['sm-icon-btn', 'active' => request()->routeIs('setting')]) aria-label="Settings">
                <i class="fa-solid fa-gear" aria-hidden="true"></i>
            </a>

            @if ($customer)
                <a href="{{ route('hub.index') }}" class="sm-account" aria-label="My account">
                    <span class="sm-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($customer->name ?? 'U'), 0, 1)) }}</span>
                    <span class="sm-account-name">{{ $firstName }}</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="sm-login" aria-label="Login or register">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    <span class="sm-hide-sm">Login &amp; Register</span>
                </a>
            @endif

            {{-- cart: icon with a count bubble, like an app --}}
            <a href="{{ route('cart.index') }}" class="sm-cart-icon" aria-label="My cart">
                <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                <b class="cart-count" @style(['display: none' => $cartCount < 1])>{{ $cartCount }}</b>
            </a>
        </div>
    </div>
</header>
