{{-- Live search panel. Driven by resources/js/hmsearch.js, which fills .hm-sp-body from the search route. --}}
<form class="hm-sp" role="search" action="{{ route('homemarket.search') }}" method="get" hidden
      data-endpoint="{{ route('homemarket.search') }}">
    <div class="hm-sp-bar">
        <button type="button" class="hm-sp-back" aria-label="Close search"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <input type="search" name="q" class="hm-sp-input" placeholder="Search products, brands or shops" autocomplete="off" enterkeyhint="search" aria-label="Search">
        <button type="button" class="hm-sp-clear" aria-label="Clear search" hidden><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="hm-sp-body" aria-live="polite"></div>

    {{-- Shown when the box is empty (next to the recent searches) --}}
    <template class="hm-sp-popular">
        @foreach (config('homemarket.nav_categories') as $category)
            @if ($category['category_id'])
                <a href="{{ route('homemarket.home', ['category' => $category['category_id']]) }}">{{ $category['label'] }}</a>
            @endif
        @endforeach
    </template>
</form>
<div class="hm-sp-scrim" hidden></div>
