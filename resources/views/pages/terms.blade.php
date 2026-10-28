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
<<<<<<< HEAD
    <li>Set your delivery location to see shops within {{ $radiusKm }} km from you.</li>
=======
    <li>Set your delivery location to see shops within {{ $radiusKm }} km of you.</li>
>>>>>>> 17e23ff28b57e0fd1ae7d9a48743b1140c30e506
    <li>Your cart holds items from <strong>one shop at a time</strong>.</li>
    <li>Items are held for you for a short while. If you leave your cart for too long, they may be released.</li>
    <li>Prices are set by the shops and shown in KES. Photos are for illustration.</li>
</ul>

<h2>Payment</h2>
<ul>
    <li>Pay with M-Pesa. Your order is confirmed once payment goes through.</li>
    <li>Any delivery fee is shown at checkout before you pay.</li>
</ul>

<h2>Delivery</h2>
<ul>
    <li>A local rider brings your order. Delivery times are estimates.</li>
    <li>Please be available and reachable on your phone when your order arrives.</li>
</ul>

<h2>Returns and refunds</h2>
<ul>
    <li>Something missing, damaged or wrong? <a href="{{ route('page', 'contact') }}">Contact us</a> as soon as possible with your order number.</li>
<<<<<<< HEAD
    <li>Items Under Subdivisions; HomeMarket, Farm Market, Alene One and Liquor Hub must be reported within 2hrs of delivery .</li>
=======
>>>>>>> 17e23ff28b57e0fd1ae7d9a48743b1140c30e506
    <li>We'll work with the shop to replace it or refund you.</li>
    <li>Refunds go back to the M-Pesa number that paid.</li>
</ul>

<h2>Age-restricted items</h2>
<p>Alcohol is only sold to customers aged 18 and over. The rider may ask for ID.</p>

<h2>Fair use</h2>
<p>Please don't misuse Smart Market – no fake orders, fraud or abuse. We may close accounts that do.</p>

<h2>Changes</h2>
<p>We may update these terms from time to time. The date at the top shows the latest version.</p>
