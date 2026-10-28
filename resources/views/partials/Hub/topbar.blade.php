{{-- Hub top bar: page title, rider online switch (riders only), notifications and avatar. --}}
@php
    $customer = auth('customer')->user();
    $hasRiderAccount = \App\Models\Hub\Rider::where('customer_id', $customer->id)->exists();

    // most specific first
    $titles = [
        'hub.index'                     => 'Dashboard',
        'hub.orders'                    => 'Orders',
        'hub.referral*'                 => 'Referrals',
        'hub.deliveries*'               => 'Deliveries',
        'hub.account.finance'           => 'Finance',
        'hub.account.marketer*'         => 'Marketer',
        'hub.marketer*'                 => 'Marketer',
        'hub.account.rider.application' => 'Rider application',
        'hub.account.rider.apply'       => 'Rider application',
        'hub.account.rider.view'        => 'Rider application',
        'hub.account.rider*'            => 'Rider',
        'hub.account.join-team'         => 'Join the team',
        'hub.account.*'                 => 'My account',
    ];
    $pageTitle = 'Hub';
    foreach ($titles as $pattern => $label) {
        if (request()->routeIs($pattern)) { $pageTitle = $label; break; }
    }

    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = \Illuminate\Support\Str::of($customer->name ?? '')->explode(' ')->first();

    // "Leave Hub" button: remember the page the person came from (a shop, the cart, the main
    // site…) when they enter the Hub. Hub pages, login/register pages and other sites are ignored,
    // so moving around inside the Hub keeps the original page.
    $from = request()->headers->get('referer');
    if ($from) {
        $fromHost = parse_url($from, PHP_URL_HOST);
        $fromPath = '/' . ltrim((string) parse_url($from, PHP_URL_PATH), '/');
        $internal = $fromHost === request()->getHost();
        $skip = preg_match('#^/(hub|login|register|logout|password|otp|verify|auth)(/|$)#i', $fromPath);
        if ($internal && !$skip) {
            session(['hub.return' => $from]);
        }
    }
    $hubReturn = session('hub.return')
        ?: (app(\App\Services\SubdivisionBranding::class)->shopping(session('shopping.subdivision'))['homeUrl'] ?? route('home'));

    $mediaBase = rtrim(config('app.media_url'), '/');
    $avatar = $customer->profile_image
        ? $mediaBase . '/' . ltrim($customer->profile_image, '/')
        : $mediaBase . '/img/Customer/Profiles/user.png';
@endphp

<header class="topbar hub-top">
    {{-- #menu collapses the sidebar on desktop; .menu-sidebar opens the drawer on phones (hub.js) --}}
    <button type="button" class="hub-icon-btn close-sidebar" id="menu" aria-label="Collapse menu">
        <i class="fa-solid fa-bars-staggered" aria-hidden="true"></i>
    </button>
    <button type="button" class="hub-icon-btn menu-sidebar" aria-label="Open menu">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>

    <div class="hub-title">
        <small>{{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}</small>
        <h1>{{ $pageTitle }}</h1>
    </div>

    <div class="hub-top-actions">
        @if ($hasRiderAccount)
            {{-- riderLocation.js fills in this button (online / offline / busy / suspended) --}}
            <div class="activator rider">
                <button type="button" id="riderOnlineBtn">Loading...</button>
            </div>
        @endif

        {{-- light / dark switch (resources/js/hub.js) --}}
        <button type="button" class="hub-icon-btn hub-theme-btn" data-hub-theme aria-pressed="false" aria-label="Switch to dark mode" title="Dark mode">
            <i class="fa-regular fa-moon is-moon" aria-hidden="true"></i>
            <i class="fa-regular fa-sun is-sun" aria-hidden="true"></i>
        </button>

        <button type="button" class="hub-icon-btn count-holder" aria-label="Notifications"
                onclick="document.getElementById('Notif') && showModal('Notif')">
            <i class="fa-regular fa-bell" aria-hidden="true"></i>
            <span class="counter"></span>
        </button>

        {{-- back to the page they were on before opening the Hub --}}
        <a href="{{ $hubReturn }}" class="hub-icon-btn hub-leave" data-no-soft aria-label="Back to where you were" title="Back to where you were">
            <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i>
        </a>
    </div>
</header>
