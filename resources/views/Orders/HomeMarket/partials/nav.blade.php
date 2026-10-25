@php
    $cartCount = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
    $customer = auth('customer')->user();
@endphp

<nav class="nav">
    <section class="nav-top">

        <a href="{{ route('homemarket.home') }}" aria-label="HomeMarket home">
            <div class="logo">
                <img src="{{ $logoUrl }}" alt="">
                <span>HOME MARKET</span>
            </div>
        </a>

        <div class="search-bar search-items">
            <i class="fa-solid fa-xmark close-search search-close-btn"></i>
            <input type="search" placeholder="Search from shops near you…" aria-label="Search shops near you">
            <i class="fa-solid fa-magnifying-glass search-show-btn"></i>
            @include('HomeMarket.partials.search-box')
        </div>

        <div class="nav-left">
            <button type="button" class="col account" onclick="showModal('mapModal')">
                <i class="ri-map-pin-line map-pin"></i>
                <span class="col-group">
                    <h6>Deliver to</h6>
                    <h4 id="current-delivery-location">
                        {{ $customer->delivery_address ?? 'Select delivery location' }}
                    </h4>
                </span>
            </button>

            <a href="{{ $customer ? route('hub.index') : route('login') }}" class="col account">
                <i class="ri-user-line"></i>
                <span>{{ $customer?->name ?? 'Login & Register' }}</span>
            </a>

            <a href="{{ route('cart.index') }}" class="col cart" aria-label="My cart">
                <i class="fa-solid fa-cart-shopping">
                    @if ($cartCount > 0)
                        <h5 class="cart-count" data-count="{{ $cartCount }}">{{ $cartCount }}</h5>
                    @endif
                </i>
                <span>My cart</span>
            </a>

            <i class="fa-solid fa-bars menu m-items-show-btn"></i>
        </div>
    </section>

    {{-- Sub-categories only. Edit the list in config/homemarket.php (nav_categories). --}}
    <section class="nav-bottom category-links m-items">
        <i class="fa-solid fa-xmark close-menu m-items-close-btn"></i>
        <div class="links">
            <a href="{{ route('homemarket.home') }}" @class(['category-link', 'active' => !request()->filled('category')])>
                <i class="ri-dashboard-line"></i>All categories
            </a>

            @foreach (config('homemarket.nav_categories') as $category)
                @if ($category['category_id'])
                    <a href="{{ route('homemarket.home', ['category' => $category['category_id']]) }}"
                       @class(['category-link', 'active' => (int) request('category') === $category['category_id']])>
                        {{ $category['label'] }}
                    </a>
                @else
                    <a href="#" class="category-link">{{ $category['label'] }}</a>
                @endif
            @endforeach
        </div>
    </section>
</nav>
