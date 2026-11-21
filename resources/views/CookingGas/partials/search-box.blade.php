{{-- Live search panel (same as Home Market's). resources/js/hmsearch.js fills .hm-sp-body from ProductController@search (JSON). --}}
<form class="hm-sp" role="search" action="{{ route('search') }}" method="get" hidden
      data-endpoint="{{ route('search') }}">
    <div class="hm-sp-bar">
        <button type="button" class="hm-sp-back" aria-label="Close search"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <input type="search" name="q" class="hm-sp-input" placeholder="Search gas brands" autocomplete="off" enterkeyhint="search" aria-label="Search">
        <button type="button" class="hm-sp-clear" aria-label="Clear search" hidden><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="hm-sp-body" aria-live="polite"></div>

    {{-- Shown when the box is empty (next to the recent searches) --}}
    <template class="hm-sp-popular">
        @foreach (['K-Gas', 'Total', 'Pro Gas', 'Hashi', 'Afrigas'] as $brand)
            <a href="{{ route('search', ['q' => $brand]) }}">{{ $brand }}</a>
        @endforeach
    </template>
</form>
<div class="hm-sp-scrim" hidden></div>
