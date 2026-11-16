{{--
    "Shop by category" tiles for the product shops (Web\Concerns\ShopPages::shopHome → $shop).
    Offer tiles first, only when they have something:
      Hot sales          best-selling discounts in shops that deliver to this buyer → <shop>.deals
      Online exclusives  every discount from shops with an active e-commerce subscription → <shop>.exclusives
    (each uses the shop's own picture from config/<shop>.php category_tiles when there is one),
    then every category with items for this buyer, biggest first, with "N on offer" when it has
    discounts, and a picture (config tile picture, else a product photo, else an icon).
    While the shop has no categories in its database, $fallback (the shop's own tiles partial) is shown.
--}}
@php
    $tiles = collect($shop['tiles'] ?? []);
@endphp

@if ($tiles->isEmpty() && empty($shop['deals']) && empty($shop['exclusives']))
    @include($fallback)
@else
    <section class="sp-cats" aria-labelledby="spCatsTitle">
        <div class="sp-section-head">
            <h2 id="spCatsTitle">Shop by category</h2>
            @if ($tiles->count() + (!empty($shop['deals']) ? 1 : 0) + (!empty($shop['exclusives']) ? 1 : 0) > 16)
                <button type="button" class="sp-row-more" data-sp-expand="spCatGrid" aria-expanded="false">
                    <span>See all</span> <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                </button>
            @endif
        </div>

        <div class="sp-cat-grid" id="spCatGrid">
            @if (!empty($shop['deals']))
                <a href="{{ $shop['dealsUrl'] }}" class="sp-cat sp-cat--hot">
                    <span class="sp-cat-pic" aria-hidden="true">
                        @if (!empty($shop['hotPic']))
                            <img src="{{ $shop['hotPic'] }}" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-fire'}))">
                        @else
                            <i class="fa-solid fa-fire"></i>
                        @endif
                    </span>
                    <span class="sp-cat-name">Hot sales</span>
                    @if (!empty($shop['dealsTop']))
                        <b class="sp-cat-badge">Up to {{ $shop['dealsTop'] }}% off</b>
                    @endif
                </a>
            @endif

            @if (!empty($shop['exclusives']))
                <a href="{{ $shop['exclusivesUrl'] }}" class="sp-cat sp-cat--excl">
                    <span class="sp-cat-pic" aria-hidden="true">
                        @if (!empty($shop['exclPic']))
                            <img src="{{ $shop['exclPic'] }}" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-bolt'}))">
                        @else
                            <i class="fa-solid fa-bolt"></i>
                        @endif
                    </span>
                    <span class="sp-cat-name">Online exclusives</span>
                    <b class="sp-cat-badge">{{ $shop['exclusives'] }} {{ \Illuminate\Support\Str::plural('offer', $shop['exclusives']) }}</b>
                </a>
            @endif

            @foreach ($tiles as $tile)
                <a href="{{ $tile->url }}" class="sp-cat">
                    <span class="sp-cat-pic" aria-hidden="true">
                        @if ($tile->image)
                            <img src="{{ $tile->image }}" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'{{ $tile->icon }}'}))">
                        @else
                            <i class="{{ $tile->icon }}"></i>
                        @endif
                    </span>
                    <span class="sp-cat-name">{{ $tile->name }}</span>
                    @if (!empty($tile->offers))
                        <b class="sp-cat-badge sp-cat-badge--soft">{{ $tile->offers }} on offer</b>
                    @endif
                </a>
            @endforeach
        </div>
    </section>
@endif
