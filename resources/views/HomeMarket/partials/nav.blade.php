<nav class="nav">
    <section class="nav-top">

        <a href="{{ route('homemarket.home') }}">
            <div class="logo">
                <img src="{{ $logoUrl }}" alt="HomeMarket Logo">
                <span>HOME MARKET</span>
            </div>
        </a>
        <div class="search-bar search-items">
            <i class="fa-solid fa-xmark close-search search-close-btn"></i>
            <input type="text" placeholder="search from shops near you . .">
            <i class="fa-solid fa-magnifying-glass search-show-btn"></i>
        </div>
        <div class="nav-left">
            <div class="col account"  onclick="showModal('mapModal')">
                <i class="ri-map-pin-line map-pin"></i>
                <span class="col-group">
                    <h6>Deliver To</h6>
                    <h4 id="current-delivery-location">
                        {{ auth('customer')->user()->delivery_address ?? 'Select delivery location' }}
                    </h4>
                </span>
            </div>
            <a href="{{ auth('customer')->check() ? route('hub.index') : route('login') }}" class="col account">
                <i class="ri-user-line"></i>
                <span>{{ auth('customer')->user()?->name ?? 'Login & Register' }}</span>
            </a>
            <a href="{{ route('cart.index') }}" class="col cart">
                <i class="fa-solid fa-cart-shopping">
                    @php
                        $cartCount = \App\Models\Customer\Cart::forCurrent()
                            ->sum('quantity');
                    @endphp

                    @if ($cartCount > 0)
                        <h5 class="cart-count" data-count="{{ $cartCount }}">
                            {{ $cartCount }}
                        </h5>
                    @endif
                </i>

                <span>- My cart</span>
            </a>
            <i class="fa-solid fa-bars menu m-items-show-btn"></i>
        </div>
    </section>
    <section class="nav-bottom category-links m-items">
         <i class="fa-solid fa-xmark close-menu m-items-close-btn"></i>
        <div class="links">
            <a href="home.html" class="category-link active">
                <i class="ri-dashboard-line"></i>
                all categories
            </a>
            <a href="foods.html" class="category-link">foods</a>
            <a href="#" class="category-link">House Hold</a>
            <a href="#" class="category-link">Confectionery</a>
            <a href="#" class="category-link">Beverage</a>
            <a href="#" class="category-link">Deterget</a>
        </div>
    </section>
</nav>

@include('HomeMarket.partials.search-box')