{{-- Open shops inside the buyer's delivery radius, nearest first (ShopDiscovery::nearbyShops).
     Each opens the shop's page (glamroom.shop); "See all" opens Shops near you. --}}
@if ($nearbyShops->isNotEmpty())
    <section class="hm-near" aria-label="Shops near you">
        <div class="hm-section-head">
            <h2>Shops near you</h2>
            <small><i class="fa-solid fa-location-dot" aria-hidden="true"></i> within {{ $radiusKm }} km · <a href="{{ route('glamroom.shops') }}">See all</a></small>
        </div>

        <div class="hm-shops-row">
            @foreach ($nearbyShops as $shop)
                <a class="hm-shop" href="{{ route('glamroom.shop', $shop->account) }}" aria-label="{{ $shop->name }}: see what it sells">
                    <span class="hm-shop-mark" aria-hidden="true">{{ $shop->initial }}<i class="hm-shop-open" title="Open"></i></span>
                    <div class="hm-shop-text">
                        <strong>{{ $shop->name }}</strong>
                        <small>
                            <span><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $shop->distance_km < 0.1 ? 'Under 100 m' : $shop->distance_km . ' km' }}</span>
                            <span><i class="fa-regular fa-clock" aria-hidden="true"></i> ~{{ $shop->eta_min }} min</span>
                        </small>
                    </div>
                    <i class="fa-solid fa-chevron-right hm-shop-go" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    </section>
@endif
