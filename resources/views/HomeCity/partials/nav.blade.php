<nav class="nav">
    <section class="nav-top">
        <a href="{{ url('/') }}">
            <div class="logo">
                <img src="{{ asset('img/logo/homecity.png') }}" alt="">
                <span>HOME CITY</span>
            </div>
        </a>
        <div class="search-bar search-items">
            <i class="fa-solid fa-xmark close-search search-close-btn"></i>
            <input type="text" placeholder="search here . . .">
            <i class="fa-solid fa-magnifying-glass search-show-btn"></i>
        </div>
        <div class="nav-left">
            <div class="col account" onclick="showModal('signUp')">
                <i class="ri-user-line"></i>
                <span>Login & Register</span>
            </div>
            <div class="col cart" onclick="showModal('cart')">
                <i class="fa-solid fa-cart-shopping">
                    <h5>2</h5>
                </i>
                <span>- My Reservations</span>
            </div>
            <i class="fa-solid fa-bars menu m-items-show-btn"></i>
        </div>
    </section>

    <section class="category-links nav-bottom m-items">
        <i class="fa-solid fa-xmark close-menu m-items-close-btn"></i>
        <div class="links">
            <a href="{{ url('/') }}" class="category-link active">Home</a>
            <a href="#" class="category-link">Residential</a>
            <a href="#" class="category-link">Commercial</a>
            <a href="#" class="category-link">Land</a>
            <a href="#" class="category-link">Warehouse</a>
            <a href="#" class="category-link">Rentals</a>
            <a href="#" class="category-link">On Sale</a>
        </div>
    </section>
</nav>
