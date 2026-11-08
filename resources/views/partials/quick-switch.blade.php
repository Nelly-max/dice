{{--
    Quick switch: a round button floating at the bottom of every subdivision page. Tapping it
    opens a panel with the other subdivisions (from the hub's sub_divisions table) and quick
    links (Smart Market home, cart, account, orders, delivery location, settings, help).

    Used by the subdivision layouts (HomeMarket, LiquorHub, CookingGas, HomeCity, subdivision/details).
    Styles: resources/css/quick-switch.css · behaviour: resources/js/quick-switch.js
--}}
@php
    $qsSubdivisions = \Illuminate\Support\Facades\Cache::remember('quickswitch.subdivisions', now()->addMinutes(10), function () {
        try {
            return \App\Models\Subdivision::query()
                ->where('status', 'active')
                ->whereNotNull('weblink')
                ->orderBy('name')
                ->get(['name', 'weblink', 'logo'])
                ->map(fn ($s) => ['name' => $s->name, 'weblink' => $s->weblink, 'logo' => $s->logo])
                ->all();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    });

    $qsCurrent = strtolower((string) request()->segment(1));
    $qsMedia = rtrim((string) config('app.media_url'), '/');
    $qsCustomer = auth('customer')->user();
    $qsCart = \App\Models\Customer\Cart::forCurrent()->sum('quantity');
    $qsHasMap = $qsHasMap ?? true;   // layouts without the delivery map sheet pass false
@endphp

<div class="qs" data-qs>
    <button type="button" class="qs-fab" data-qs-toggle aria-expanded="false" aria-controls="qsPanel" aria-label="Switch service">
        <i class="fa-solid fa-grip qs-fab-open" aria-hidden="true"></i>
        <i class="fa-solid fa-xmark qs-fab-close" aria-hidden="true"></i>
        <b class="qs-fab-count cart-count" @style(['display: none' => $qsCart < 1])>{{ $qsCart }}</b>
    </button>

    <div class="qs-scrim" data-qs-close hidden></div>

    <div class="qs-panel" id="qsPanel" role="dialog" aria-label="Switch service" hidden>
        <div class="qs-head">
            <strong>Switch service</strong>
            <button type="button" class="qs-x" data-qs-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>

        @if (count($qsSubdivisions))
            <ul class="qs-grid" aria-label="Services">
                @foreach ($qsSubdivisions as $sub)
                    @php $isHere = strtolower($sub['weblink']) === $qsCurrent; @endphp
                    <li>
                        <a href="{{ url('/' . ltrim($sub['weblink'], '/')) }}" @class(['qs-tile', 'is-here' => $isHere]) @if ($isHere) aria-current="page" @endif>
                            <span class="qs-tile-logo">
                                @if (!empty($sub['logo']))
                                    <img src="{{ $qsMedia . '/' . ltrim($sub['logo'], '/') }}" alt="" loading="lazy">
                                @else
                                    <b>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($sub['name'], 0, 1)) }}</b>
                                @endif
                            </span>
                            <span class="qs-tile-name">{{ $sub['name'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="qs-links" aria-label="Shortcuts">
            <a href="{{ route('home') }}" class="qs-link">
                <i class="fa-solid fa-house" aria-hidden="true"></i><span>Home</span>
            </a>
            <a href="{{ route('cart.index') }}" class="qs-link">
                <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i><span>Cart</span>
                <b class="cart-count" @style(['display: none' => $qsCart < 1])>{{ $qsCart }}</b>
            </a>
            @if ($qsCustomer)
                <a href="{{ route('hub.orders') }}" class="qs-link">
                    <i class="fa-solid fa-receipt" aria-hidden="true"></i><span>Orders</span>
                </a>
                <a href="{{ route('hub.index') }}" class="qs-link">
                    <i class="fa-regular fa-user" aria-hidden="true"></i><span>Account</span>
                </a>
            @else
                <a href="{{ route('login') }}" class="qs-link">
                    <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span>Login</span>
                </a>
            @endif
            @if ($qsHasMap)
                <button type="button" class="qs-link" data-qs-location>
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>Location</span>
                </button>
            @endif
            <a href="{{ route('setting') }}" class="qs-link">
                <i class="fa-solid fa-gear" aria-hidden="true"></i><span>Settings</span>
            </a>
            <a href="{{ route('page', 'help') }}" class="qs-link">
                <i class="fa-regular fa-circle-question" aria-hidden="true"></i><span>Help</span>
            </a>
        </div>
    </div>
</div>
