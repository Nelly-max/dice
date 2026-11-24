{{--
    Filter chips on the Home City / Airbnb results pages (App\Support\ListingFilters::apply()).
    Row 1: property types from the database (Apartment, Bungalow, Commercial plot …).
    Row 2: the unit types of the chosen property type (Bedsitter, 2 Bedroom …), or straight
    away when the page has a single property type. Links keep ?type=, ?q=, ?view=, ?radius=.
    Expects $filters (the array apply() returns) and $noun ("listing", "stay").
--}}
@php
    $chipUrl = fn (array $set) => request()->fullUrlWithQuery($set);
@endphp

@if ($filters['showTypes'] || $filters['showUnits'])
    <div class="lf" role="region" aria-label="Filter {{ \Illuminate\Support\Str::plural($noun) }}">

        @if ($filters['showTypes'])
            <div class="lf-row" role="group" aria-label="Property type">
                <a href="{{ $chipUrl(['pt' => null, 'ut' => null]) }}" @class(['lf-chip', 'is-active' => !$filters['pt']]) @if (!$filters['pt']) aria-current="true" @endif>
                    All <b>{{ $filters['types']->sum('count') }}</b>
                </a>
                @foreach ($filters['types'] as $t)
                    <a href="{{ $chipUrl(['pt' => $t->key, 'ut' => null]) }}" @class(['lf-chip', 'is-active' => $filters['pt'] === $t->key]) @if ($filters['pt'] === $t->key) aria-current="true" @endif>
                        {{ $t->name }} <b>{{ $t->count }}</b>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($filters['showUnits'])
            <div class="lf-row lf-row--units" role="group" aria-label="Unit type">
                <a href="{{ $chipUrl(['ut' => null]) }}" @class(['lf-chip', 'lf-chip--sub', 'is-active' => !$filters['ut']]) @if (!$filters['ut']) aria-current="true" @endif>
                    {{ $filters['typeName'] ? 'All ' . \Illuminate\Support\Str::plural(\Illuminate\Support\Str::lower($filters['typeName'])) : 'All' }}
                </a>
                @foreach ($filters['units'] as $u)
                    <a href="{{ $chipUrl(['ut' => $u->key]) }}" @class(['lf-chip', 'lf-chip--sub', 'is-active' => $filters['ut'] === $u->key]) @if ($filters['ut'] === $u->key) aria-current="true" @endif>
                        {{ $u->name }} <b>{{ $u->count }}</b>
                    </a>
                @endforeach
            </div>
        @endif

    </div>

    {{-- phones: keep the chosen chips in sight in their scrolling rows --}}
    <script>
        document.querySelectorAll('.lf-row').forEach(function (row) {
            var on = row.querySelector('.is-active');
            if (on && row.scrollWidth > row.clientWidth) row.scrollLeft = on.offsetLeft - (row.clientWidth - on.offsetWidth) / 2;
        });
    </script>
@endif
