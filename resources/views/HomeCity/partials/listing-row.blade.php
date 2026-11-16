{{-- Auto-sliding row of listing cards (same as HomeMarket/partials/product-row, wider cards). --}}
@if (collect($items)->isNotEmpty())
    <div class="cards-slider hc-row" data-gap="16" data-gap-sm="10" data-pad="0" data-min="250" data-min-sm="220" @isset($id) id="{{ $id }}" @endisset>
        <div class="head">
            <h3 class="sub-heading">{{ $title }}</h3>
            <span>
                <i class="fa-solid fa-angle-left" role="button" aria-label="Previous listings"></i>
                <i class="fa-solid fa-angle-right" role="button" aria-label="Next listings"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @foreach ($items as $listing)
                @include('HomeCity.Properties.property-card', ['property' => $listing])
            @endforeach
        </div>
    </div>
@endif
