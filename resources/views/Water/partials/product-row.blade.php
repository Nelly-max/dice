{{-- Auto-sliding row of water offers (same as HomeMarket/partials/product-row); the title links to $more. --}}
@if (collect($items)->isNotEmpty())
    <div class="cards-slider" data-gap="16" data-gap-sm="8" data-pad="0" data-min="176" data-min-sm="130" @isset($id) id="{{ $id }}" @endisset>
        <div class="head">
            <h3 class="sub-heading">@if (!empty($more))<a href="{{ $more }}" style="color:inherit;text-decoration:none">{{ $title }}</a>@else{{ $title }}@endif</h3>
            <span>
                <i class="fa-solid fa-angle-left" role="button" aria-label="Previous items"></i>
                <i class="fa-solid fa-angle-right" role="button" aria-label="Next items"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @foreach ($items as $offer)
                @include('Water.partials.product-card', ['offer' => $offer])
            @endforeach
        </div>
    </div>
@endif
