{{-- Live search panel. Driven by resources/js/hmsearch.js, which fills .hm-sp-body from the search route. --}}
<form class="hm-sp" role="search" action="{{ route('homemarket.search') }}" method="get" hidden
      data-endpoint="{{ route('homemarket.search') }}">
    <div class="hm-sp-bar">
        <button type="button" class="hm-sp-back" aria-label="Close search"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <input type="search" name="q" class="hm-sp-input" placeholder="Search products, brands or shops" autocomplete="off" enterkeyhint="search" aria-label="Search">
        <button type="button" class="hm-sp-clear" aria-label="Clear search" hidden><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="hm-sp-body" aria-live="polite"></div>

    {{-- Shown when the box is empty (next to the recent searches): the offer pages, the shop's
         categories from its database (ShopCatalog), then popular searches from config/homemarket.php --}}
    @php
        $popCats = \App\Support\ShopCatalog::categories('homemarket');
        $popTerms = collect(config('homemarket.nav_categories', []))->pluck('label')
            ->reject(fn ($label) => $popCats->contains(fn ($c) => strcasecmp($c->name, $label) === 0))
            ->take(8);
    @endphp
    <template class="hm-sp-popular">
        <a href="{{ route('homemarket.deals') }}">Hot sales</a>
        <a href="{{ route('homemarket.exclusives') }}">Online exclusives</a>
        @foreach ($popCats as $popCat)
            <a href="{{ route('homemarket.category', $popCat->id) }}">{{ $popCat->name }}</a>
        @endforeach
        @foreach ($popTerms as $label)
            <a href="{{ route('homemarket.search', ['q' => $label]) }}">{{ $label }}</a>
        @endforeach
    </template>
</form>
<div class="hm-sp-scrim" hidden></div>
