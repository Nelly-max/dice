{{-- Live search panel (resources/js/hmsearch.js). Suggestions come from PropertyController@index as JSON;
     Enter opens the Home City page with ?q=. The chips below show while the box is empty. --}}
@php
    $hcPopular = ['Bedsitter', 'Single room', '1 Bedroom', '2 Bedroom', '3 Bedroom', 'Hostel', 'Shop', 'Office', 'Warehouse', 'Container', 'Plot', 'For sale'];
@endphp
<form class="hm-sp" role="search" action="{{ route('homecity.index') }}" method="get" hidden
      data-endpoint="{{ route('homecity.index') }}">
    <div class="hm-sp-bar">
        <button type="button" class="hm-sp-back" aria-label="Close search"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <input type="search" name="q" class="hm-sp-input" placeholder="Search bedsitter, 2 bedroom, Kilimani…" autocomplete="off" enterkeyhint="search" aria-label="Search">
        <button type="button" class="hm-sp-clear" aria-label="Clear search" hidden><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="hm-sp-body" aria-live="polite"></div>

    <template class="hm-sp-popular">
        @foreach ($hcPopular as $label)
            <a href="{{ route('homecity.index', ['q' => $label]) }}">{{ $label }}</a>
        @endforeach
    </template>
</form>
<div class="hm-sp-scrim" hidden></div>
