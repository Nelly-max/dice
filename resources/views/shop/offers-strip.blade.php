{{--
    Two bright cards under the category tiles (product shops): Hot sales near the buyer and
    Online exclusives. Each shows only when it has offers. Expects $shop (ShopPages::shopHome).
    The countdown only appears when an offer on the page really ends within 7 days.
--}}
@if (!empty($shop['deals']) || !empty($shop['exclusives']))
    <section class="sp-offers" aria-label="Offers">
        @if (!empty($shop['deals']))
            <a href="{{ $shop['dealsUrl'] }}" class="sp-offer sp-offer--hot">
                <span class="sp-offer-icon" aria-hidden="true"><i class="fa-solid fa-fire"></i></span>
                <span class="sp-offer-text">
                    <small>Hot sales near you</small>
                    <strong>{{ $shop['deals'] }} {{ \Illuminate\Support\Str::plural('offer', $shop['deals']) }} today</strong>
                    @if (!empty($shop['endsAt']))
                        <em class="sp-countdown" data-countdown="{{ $shop['endsAt']->toIso8601String() }}">Ends soon</em>
                    @endif
                </span>
                <span class="sp-offer-go">Shop deals <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            </a>
        @endif

        @if (!empty($shop['exclusives']))
            <a href="{{ $shop['exclusivesUrl'] }}" class="sp-offer sp-offer--excl">
                <span class="sp-offer-icon" aria-hidden="true"><i class="fa-solid fa-bolt"></i></span>
                <span class="sp-offer-text">
                    <small>Online exclusives</small>
                    <strong>{{ $shop['exclusives'] }} {{ \Illuminate\Support\Str::plural('offer', $shop['exclusives']) }} on {{ $shop['meta']['name'] }}</strong>
                </span>
                <span class="sp-offer-go">See all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            </a>
        @endif
    </section>
@endif
