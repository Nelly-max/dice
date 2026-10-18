<nav class="hub-nav">
    <div class="left">
        <a href="{{ route('home') }}">
            <i class="fa-solid fa-chevron-left"></i>
            <h6>Home</h6>
        </a>
    </div>
    @php
        $hasRiderAccount = \App\Models\Hub\Rider::where(
            'customer_id',
            auth('customer')->id()
        )->exists();
    @endphp

    @if($hasRiderAccount)
        <div class="activator rider">
            <button id="riderOnlineBtn">
                Loading...
            </button>
        </div>
    @endif

    <div class="right">
        <h5>Welcome</h5>
        <h4>{{ auth('customer')->user()?->name ?? 'Welcome' }}</h4>
    </div>
</nav>