@php
    $radiusKm = config('homemarket.max_radius_m', 3000) / 1000;
@endphp

<p class="sm-lede">
    Smart Market brings the shops, services and homes around you into one place. Order everyday items from
    shops in your neighbourhood, find a home or a service, pay with M-Pesa and get it delivered by a local rider.
</p>

<h2>What you can do on Smart Market</h2>
<div class="sm-feature-grid">
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-basket-shopping" aria-hidden="true"></i></span>
        <h3>Shop near you</h3>
        <p>Groceries and household items from shops within {{ $radiusKm }} km, through Home Market and other stores.</p>
    </div>
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
        <h3>Find homes and spaces</h3>
        <p>Browse houses, hostels, offices and land listed on Home City.</p>
    </div>
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-fire-flame-simple" aria-hidden="true"></i></span>
        <h3>Cooking gas and more</h3>
        <p>Refills and other everyday services, with new services added over time.</p>
    </div>
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i></span>
        <h3>Pay with M-Pesa</h3>
        <p>Simple, familiar payment, with an invoice for every order.</p>
    </div>
</div>

<h2>How it works</h2>
<ol class="sm-steps">
    <li><strong>Set your delivery location.</strong> We show only shops that can deliver to you, nearest first.</li>
    <li><strong>Fill your cart from one shop.</strong> Items are held for you while you shop.</li>
    <li><strong>Pay with M-Pesa.</strong> The shop prepares your order as soon as payment is confirmed.</li>
    <li><strong>A nearby rider delivers.</strong> Follow your order in <em>My orders</em>.</li>
</ol>

<h2>Work with us</h2>
<p>Shops, riders and marketers can join Smart Market. Sign in and open <em>My account → Join the team</em>.</p>
