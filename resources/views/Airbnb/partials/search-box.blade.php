{{-- Live search panel (resources/js/hmsearch.js). Suggestions come from StayController@index as JSON;
     Enter opens the Airbnb page with ?q=. The chips below show while the box is empty. --}}
<form class="hm-sp" role="search" action="{{ route('airbnb.index') }}" method="get" hidden
      data-endpoint="{{ route('airbnb.index') }}">
    <div class="hm-sp-bar">
        <button type="button" class="hm-sp-back" aria-label="Close search"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <input type="search" name="q" class="hm-sp-input" placeholder="Search studio, 1 bedroom, Kilimani…" autocomplete="off" enterkeyhint="search" aria-label="Search">
        <button type="button" class="hm-sp-clear" aria-label="Clear search" hidden><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="hm-sp-body" aria-live="polite"></div>

    <template class="hm-sp-popular">
        @foreach (config('airbnb.types', []) as $key => [$label, $icon])
            <a href="{{ route('airbnb.index', ['type' => $key]) }}">{{ $label }}</a>
        @endforeach
        @foreach (['Bedsitter', 'Pool', 'Villa'] as $label)
            <a href="{{ route('airbnb.index', ['q' => $label]) }}">{{ $label }}</a>
        @endforeach
    </template>
</form>
<div class="hm-sp-scrim" hidden></div>
