<nav class="main-nav">
    <section class="main-nav-top">
        <a href="home.html">
            <div class="logo">
                <img src="{{ asset('img/logo/cookingGas.png') }}" alt="">
                <span>COOKING GAS</span>
            </div>
        </a>
        <div class="search-bar search-items">
            <i class="fa-solid fa-xmark close-search search-close-btn"></i>

            <input type="text" id="searchInput" placeholder="search here . . .">
            <i class="fa-solid fa-magnifying-glass search-show-btn"></i>
        </div>
        <div class="main-nav-left">
            <div class="col account" onclick="showModal('signUp')">
                <i class="ri-user-line"></i>
                <span>Login & Register</span>
            </div>
            <div class="col cart" onclick="showModal('cart')">
                <i class="fa-solid fa-cart-shopping">
                    <h5>2</h5>
                </i>
                
                <span>- My Orders</span>
            </div>
            <i class="fa-solid fa-bars menu m-items-show-btn"></i>
        </div>
    </section>
    <section class="category-links main-nav-bottom m-items">
        <i class="fa-solid fa-xmark close-menu m-items-close-btn"></i>
        <div class="links">
            <a href="home.html" class="category-link active">Home</a>
            <a href="#" class="category-link">Gas Cylinders</a>
            <a href="#" class="category-link">Accesories</a>
            <a href="#" class="category-link">Brands</a>
            <a href="#" class="category-link">Services</a>
        </div>
    </section>
    <div class="search-results" id="searchResults">
        
    </div>   
</nav>