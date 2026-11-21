{{-- Auto-sliding row of gas cards (same as HomeMarket/partials/product-row). JS hooks: .cards-slider .column-cards .card-data and the two arrows --}}
@if (collect($cylinders)->isNotEmpty())
    <div class="cards-slider" data-gap="16" data-gap-sm="8" data-pad="0" data-min="176" data-min-sm="130" @isset($id) id="{{ $id }}" @endisset>
        <div class="head">
            <h3 class="sub-heading">{{ $title }}</h3>
            <span>
                <i class="fa-solid fa-angle-left" role="button" aria-label="Previous items"></i>
                <i class="fa-solid fa-angle-right" role="button" aria-label="Next items"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('CookingGas.Products.cylinders', ['cylinders' => $cylinders])
        </div>
    </div>
@endif
