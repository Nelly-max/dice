@php
    $radiusKm = config('homemarket.max_radius_m', 3000) / 1000;
@endphp

<p class="sm-lede">
    By using Smart Market you agree to these simple terms. Please also read our
    <a href="{{ route('page', 'privacy-policy') }}">Privacy Policy</a>.
</p>

<h2>Your account</h2>
<ul>
    <li>Use your real details and keep your password private.</li>
    <li>You're responsible for orders placed from your account.</li>
</ul>

<h2>Shopping</h2>
<ul>
    <li>Set your delivery location to see shops within {{ $radiusKm }} km from you.</li>
    <li>Your cart can hold items from several subdivisions, but from <strong>one shop in each subdivision</strong>. Each subdivision is checked out and delivered separately.</li>
    <li>Items are held for you for a short while. If you leave your cart for too long, they may be released.</li>
    <li>Prices are set by the shops and shown in KES. Photos are for illustration.</li>
</ul>

<h2>Payment</h2>
<ul>
    <li>Pay with M-Pesa. Your order is confirmed once payment goes through.</li>
    <li>The delivery fee is worked out from the road distance between the shop and your delivery location, and is shown at checkout before you pay.</li>
    <li>Your delivery location is the one you chose while shopping and can't be changed at checkout.</li>
</ul>

<h2>Delivery</h2>
<ul>
    <li>A local rider brings your order. Delivery times are estimates.</li>
    <li>Please be available and reachable on your phone when your order arrives.</li>
</ul>

<h2>Returns and refunds</h2>
<ul>
    <li>Something missing, damaged or wrong? <a href="{{ route('page', 'contact') }}">Contact us</a> as soon as possible with your order number.</li>
    <li>Items under the subdivisions Home Market, Farm Market, Alene One and Liquor Hub must be reported within 2 hours of delivery.</li>
    <li>We'll work with the shop to replace it or refund you.</li>
    <li>Refunds go back to the M-Pesa number that paid.</li>
</ul>

<h2>Age-restricted items</h2>
<p>Alcohol on Liquor Hub is only sold to customers aged 18 and over. The rider may ask for ID.</p>

<h2>Homes and stays</h2>
<ul>
    <li>Home City and Airbnb listings are posted by the property owners or managers. Check the property and agree terms with the lister before paying.</li>
    <li>Prices, availability and vacant units are set by the lister and can change.</li>
</ul>

<h2>Fair use</h2>
<p>Please don't misuse Smart Market – no fake orders, fraud or abuse. We may close accounts that do.</p>

<h2>Changes</h2>
<p>We may update these terms from time to time. The date at the top shows the latest version.</p>
