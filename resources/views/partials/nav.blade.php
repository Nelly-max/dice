<nav class="navigation">
    <div class="nav-top">
        <a href="{{ route('home') }}" class="logo">
            <h2>SMART MARKET</h2>
        </a>
        <div class="flex-nav">
            <a href="{{ route('home') }}" class="small-nav hid-top">
                <img src="{{ asset('img/home_icon.png') }}" class="nav" alt="">
                <h4>Home</h4>
            </a>
            <a href="#" class="small-nav hid-top">
                <i class="fa-solid fa-layer-group nav"></i>
                <h4>Items</h4>
            </a>
            <a href="#" class="small-nav">
                <i class="fa-regular fa-bookmark nav"></i>
                <h5>{{ $savedCount ?? 0 }}</h5>
                <h4>Saved</h4>
            </a>                
            <a href="{{ route('cart.view') }}" class="small-nav active">
                <i class="fa-solid fa-cart-shopping" style="color:rgb(9, 161, 9)"></i>
                <h5>{{ $cartCount ?? 0 }}</h5>
                <h4>Cart</h4>
            </a>
            <a href="{{ route('setting') }}" class="small-nav">
                <i class="fa-solid fa-gear nav"></i>
                <h4>Settings</h4>
            </a>
        </div>
        <div class="static-nav">
            <div class="notification">
                <i class="fa-regular fa-bell"></i>
                <h5>{{ $notificationsCount ?? 0 }}</h5>
            </div>
            <a href="#" class="profile-area">
                <img src="{{ asset('img/user.png') }}" alt="">
                <div class="profile-name">
                    <h3>Hello</h3>
                    <h4>{{ auth()->user()->name ?? 'Guest' }}</h4>
                </div>
            </a>
        </div>
    </div>
    <ul class="nav-extra"> 
        <li><a href="{{ route('home') }}" class="active">Home</a></li>
        <li><a href="#">Products</a></li>
        <li><a href="#">Pro Find</a></li>
        <li><a href="#">Services</a></li>
        <li><a href="#">Bookings</a></li>
        <li><a href="#">Technology</a></li>
        <li><a href="#">Media</a></li>
    </ul>
</nav>
