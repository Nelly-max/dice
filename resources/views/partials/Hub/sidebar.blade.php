{{--
    Hub sidebar. Desktop: fixed on the left (#menu in the top bar collapses it to icons).
    Phones/tablets: a drawer opened by .menu-sidebar and closed by .remove-sidebar or the scrim
    (resources/js/hub.js toggles .close / .active on .sidebar).
--}}
@php
    $customer = auth('customer')->user();

    $hasMarketerAccount = \App\Models\Hub\Marketer::where('customer_id', $customer->id)->exists();
    $hasRiderAccount = \App\Models\Hub\Rider::where('customer_id', $customer->id)->exists();

    $mediaBase = rtrim(config('app.media_url'), '/');
    $avatar = $customer->profile_image
        ? $mediaBase . '/' . ltrim($customer->profile_image, '/')
        : $mediaBase . '/img/Customer/Profiles/user.png';
@endphp

<aside class="sidebar hub-side" aria-label="Hub menu">

    <div class="hub-side-head">
        <a href="{{ route('hub.index') }}" class="hub-brand">
            <img src="{{ asset('img/favicon.webp') }}" alt="">
            <span>Smart Market <small>Hub</small></span>
        </a>

        <button type="button" class="hub-side-close remove-sidebar" aria-label="Close menu">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <a href="{{ route('hub.account.index') }}" class="hub-user" title="My account">
        <img src="{{ $avatar }}" alt="">
        <span>
            <strong>{{ $customer->name }}</strong>
            <small>{{ $customer->account }}</small>
        </span>
    </a>

    <nav class="hub-menu">
        <p class="hub-menu-label">Overview</p>
        <a href="{{ route('hub.index') }}" @class(['hub-link', 'active' => request()->routeIs('hub.index')])>
            <i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>Dashboard</span>
        </a>

        <p class="hub-menu-label">Shopping</p>
        <a href="{{ route('hub.orders') }}" @class(['hub-link', 'active' => request()->routeIs('hub.orders')])>
            <i class="fa-solid fa-receipt" aria-hidden="true"></i><span>Orders</span>
        </a>
        <a href="#" class="hub-link is-soon" aria-disabled="true">
            <i class="fa-solid fa-bag-shopping" aria-hidden="true"></i><span>Purchases</span><em>Soon</em>
        </a>
        <a href="#" class="hub-link is-soon" aria-disabled="true">
            <i class="fa-regular fa-calendar-check" aria-hidden="true"></i><span>Bookings</span><em>Soon</em>
        </a>

        @if ($hasRiderAccount || $hasMarketerAccount)
            <p class="hub-menu-label">Work</p>
            @if ($hasRiderAccount)
                <a href="{{ route('hub.deliveries') }}" @class(['hub-link', 'active' => request()->routeIs('hub.deliveries*')])>
                    <i class="fa-solid fa-motorcycle" aria-hidden="true"></i><span>Deliveries</span>
                </a>
            @endif
            @if ($hasMarketerAccount)
                <a href="{{ route('hub.referrals') }}" @class(['hub-link', 'active' => request()->routeIs('hub.referral*')])>
                    <i class="fa-solid fa-link" aria-hidden="true"></i><span>Referrals</span>
                </a>
            @endif
        @endif

        <p class="hub-menu-label">Account</p>
        <a href="{{ route('hub.account.index') }}" @class(['hub-link', 'active' => request()->routeIs('hub.account.*')])>
            <i class="fa-regular fa-circle-user" aria-hidden="true"></i><span>My account</span>
        </a>
    </nav>

    <div class="hub-side-foot">
        <a href="{{ route('home') }}" class="hub-link">
            <i class="fa-solid fa-store" aria-hidden="true"></i><span>Back to shop</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" data-notify-loading="Signing you out…">
            @csrf
            <button type="submit" class="hub-link hub-logout">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Log out</span>
            </button>
        </form>
    </div>
</aside>

<div class="hub-scrim" data-hub-scrim aria-hidden="true"></div>
