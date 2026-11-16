{{-- Auto-sliding row of item cards. JS hooks: .cards-slider .column-cards .card-data and the two arrow icons.
     Optional: $more (the title links there), $endsAt (Carbon: countdown next to the title). --}}
@if ($products->isNotEmpty())
    <div class="cards-slider" data-gap="16" data-gap-sm="8" data-pad="0" data-min="176" data-min-sm="130" @isset($id) id="{{ $id }}" @endisset>
        <div class="head">
            <h3 class="sub-heading">@if (!empty($more))<a href="{{ $more }}" style="color:inherit;text-decoration:none">{{ $title }}</a>@else{{ $title }}@endif
                @if (!empty($endsAt))<em class="sp-countdown" data-countdown="{{ $endsAt->toIso8601String() }}">Ends soon</em>@endif
            </h3>
            <span>
                <i class="fa-solid fa-angle-left" role="button" aria-label="Previous items"></i>
                <i class="fa-solid fa-angle-right" role="button" aria-label="Next items"></i>
            </span>
        </div>
        <div class="items-container cards-container column-cards">
            @include('LiquorHub.products.items')
        </div>
    </div>
@endif
