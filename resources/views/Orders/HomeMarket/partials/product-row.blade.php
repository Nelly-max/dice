{{-- Auto-sliding row of item cards. JS hooks: .cards-slider .column-cards .card-data and the two arrow icons --}}
@if ($products->isNotEmpty())
    <div class="cards-slider" @isset($id) id="{{ $id }}" @endisset>
        <div class="head">
            <h3 class="sub-heading">{{ $title }}</h3>
            <span>
                <i class="fa-solid fa-angle-left" role="button" aria-label="Previous items"></i>
                <i class="fa-solid fa-angle-right" role="button" aria-label="Next items"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('HomeMarket.products.items')
        </div>
    </div>
@endif
