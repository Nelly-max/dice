{{-- Phone bottom bar for the main site (hidden on larger screens). --}}
@php
    $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
@endphp

<nav class="sm-tabbar" aria-label="Quick links">
    <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')]) @if (request()->routeIs('home')) aria-current="page" @endif>
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        <span>Home</span>
    </a>
    <a href="{{ route('products') }}" @class(['active' => request()->routeIs('products')])>
        <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
        <span>Products</span>
    </a>
    <a href="{{ route('hub.orders') }}">
        <i class="fa-solid fa-receipt" aria-hidden="true"></i>
        <span>Orders</span>
    </a>
    <a href="{{ route('cart.index') }}">
        <span class="sm-tab-icon">
            <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i>
            <b class="cart-count" @style(['display: none' => $cartCount < 1])>{{ $cartCount }}</b>
        </span>
        <span>Cart</span>
    </a>
    <a href="{{ route('setting') }}" @class(['active' => request()->routeIs('setting') || request()->routeIs('page')])>
        <i class="fa-solid fa-gear" aria-hidden="true"></i>
        <span>Settings</span>
    </a>
</nav>
