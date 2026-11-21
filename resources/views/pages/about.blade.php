@php
    $radiusKm = config('homemarket.max_radius_m', 3000) / 1000;
@endphp

<p class="sm-lede">
    Smart Market is the brand name for Mosaic Cart (BN-L3SMKL32), an online trading platform in Kenya where we bridge the gap between businesses and customers.
    We have a wide range of business partnerships under different categories which we term subdivisions. These include:
</p>

<ul class="sm-subdivisions">
    <li><strong>Home Market</strong> – groceries and household items from supermarkets and shops near you.</li>
    <li><strong>Liquor Hub</strong> – wines, spirits, beer and mixers from licensed liquor stores (18+ only).</li>
    <li><strong>Glam Room</strong> – beauty, skin care, hair and personal care products.</li>
    <li><strong>Farm Market</strong> – fresh produce and farm products, including organic options.</li>
    <li><strong>Cooking Gas</strong> – gas refills and cylinders from gas shops near you.</li>
    <li><strong>Water</strong> – drinking water and refills delivered to your door.</li>
    <li><strong>Home City</strong> – houses, bedsitters, hostels, shops, offices, warehouses, containers and land to rent or buy.</li>
    <li><strong>Airbnb</strong> – furnished stays you can book for a night or longer.</li>
</ul>
<p>New subdivisions are added over time; the full list is on the <a href="{{ route('products') }}">Products</a> page.</p>

<h2>What you can do on Smart Market</h2>
<div class="sm-feature-grid">
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-basket-shopping" aria-hidden="true"></i></span>
        <h3>Shop near you</h3>
        <p>Groceries, drinks, beauty and farm products from shops within {{ $radiusKm }} km, through Home Market, Liquor Hub, Glam Room and Farm Market.</p>
    </div>
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-fire-flame-simple" aria-hidden="true"></i></span>
        <h3>Gas and water</h3>
        <p>Cooking gas refills and drinking water from shops near you, delivered to your door.</p>
    </div>
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-house-chimney" aria-hidden="true"></i></span>
        <h3>Find homes and stays</h3>
        <p>Houses, hostels, offices, warehouses and land on Home City, and furnished stays on Airbnb.</p>
    </div>
    <div class="sm-feature">
        <span class="sm-feature-icon"><i class="fa-solid fa-tags" aria-hidden="true"></i></span>
        <h3>Offers every day</h3>
        <p>Hot sales and online exclusives from shops near you, with categories to browse in every shop.</p>
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
    <li><strong>Fill your cart.</strong> Shop in as many subdivisions as you like, from one shop in each. Items are held for you while you shop.</li>
    <li><strong>Check out each subdivision.</strong> Each subdivision is checked out and delivered on its own, with the delivery fee shown before you pay.</li>
    <li><strong>Pay with M-Pesa.</strong> The shop prepares your order as soon as payment is confirmed.</li>
    <li><strong>A nearby rider delivers.</strong> Follow your order in <em>My orders</em>.</li>
</ol>

<h2>Work with us</h2>
<p>Shops, riders and marketers can join Smart Market. Sign in and open <em>My account → Join the team</em>.</p>
