<nav class="hub-nav">
    <div class="left">
        <a href="{{ route('home') }}">
            <i class="fa-solid fa-chevron-left"></i>
            <h6>Home</h6>
        </a>
    </div>
    <div class="activator rider">
        <button id="riderOnlineBtn">
            Loading...
        </button>
    </div>
    <div class="right">
        <h5>Welcome</h5>
        <h4>{{ auth('customer')->user()?->name ?? 'Welcome' }}</h4>
    </div>
</nav>