{{-- Water shops inside the buyer's delivery radius, nearest first (WaterController::nearbyShops).
     Same look as Home Market's "Shops near you"; each shop opens its price list (water.shop). --}}
@if ($nearbyShops->isNotEmpty())
    <section class="hm-near" aria-label="Shops near you">
        <div class="hm-section-head">
            <h2>Shops near you</h2>
            <a href="{{ route('water.shops') }}"><small><i class="fa-solid fa-location-dot" aria-hidden="true"></i> within {{ $radiusKm }} km</small></a>
        </div>

        <div class="hm-shops-row">
            @foreach ($nearbyShops as $shop)
                <a href="{{ $shop->url }}" class="hm-shop">
                    <span class="hm-shop-mark" aria-hidden="true">{{ $shop->initial }}<i class="hm-shop-open" title="Open"></i></span>
                    <div class="hm-shop-text">
                        <strong>{{ $shop->name }}</strong>
                        <small>
                            <span><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $shop->distance_km < 0.1 ? 'Under 100 m' : $shop->distance_km . ' km' }}</span>
                            <span><i class="fa-regular fa-clock" aria-hidden="true"></i> ~{{ $shop->eta_min }} min</span>
                        </small>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif
