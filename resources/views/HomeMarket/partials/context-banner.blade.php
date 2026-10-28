{{-- Tells the buyer why they see what they see: no location, nothing in range, or cart-locked shop. --}}
@if (!$hasLocation)
    <div class="context-banner" role="status">
        <i class="fa-solid fa-location-dot"></i>
        <p><strong>Where should we deliver?</strong> Set your location to see shops within {{ config('homemarket.max_radius_m') / 1000 }} km and start adding items to your cart.</p>
        <button type="button" onclick="showModal('mapModal')"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Set location</button>
    </div>
@elseif ($outOfRange)
    <div class="context-banner" role="status">
        <i class="fa-solid fa-store-slash"></i>
        <p>No open shops deliver to this location yet. Try a different delivery location.</p>
        <button type="button" onclick="showModal('mapModal')"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Change location</button>
    </div>
@elseif ($cartShopName)
    <div class="context-banner context-banner--shop" role="status">
        <i class="fa-solid fa-store"></i>
        <p>Your are now shopping at <strong>{{ $cartShopName }}</strong> add items to cart.</p>
        <a href="{{ route('cart.index') }}"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> View cart</a>
    </div>
@endif
