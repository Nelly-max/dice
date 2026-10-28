{{-- Open shops inside the buyer's delivery radius, nearest first (ShopDiscovery::nearbyShops). --}}
@if ($nearbyShops->isNotEmpty())
    <section aria-label="Shops near you">
        <div class="hm-section-head">
            <h2>Shops near you</h2>
            <small>within {{ $radiusKm }} km</small>
        </div>

        <div class="hm-shops-row">
            @foreach ($nearbyShops as $shop)
                <div class="hm-shop">
                    <span class="hm-shop-mark" aria-hidden="true">{{ $shop->initial }}</span>
                    <div class="hm-shop-text">
                        <strong>{{ $shop->name }}</strong>
                        <small>{{ $shop->distance_km }} km · ~{{ $shop->eta_min }} min</small>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
