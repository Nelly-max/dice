@extends('layouts.main')

@section('title', 'Professionals · Smart Market')
@section('description', 'Book make-up artists, plumbers, photographers and other professionals near you on Smart Market.')

@section('content')
{{-- Professionals directory (ProsController@index): ?kind=, ?county=, ?market= (related market), ?q= --}}
@php
    use App\Support\Professionals as P;
    $here = fn (array $q) => route('pros.index', array_filter(array_merge(request()->only(['kind', 'county', 'market', 'q']), $q), fn ($v) => $v !== null && $v !== '' && $v !== 0));
@endphp
<div class="sm-page pr">
    <header class="pr-head">
        <p class="sm-greeting">Professionals</p>
        <h1>{{ $market && isset($markets[$market]) ? 'Professionals for ' . $markets[$market] : 'Book a professional' }}</h1>
        <p class="sm-lead">People near you who do the job: make-up, repairs, photography, lessons and more.</p>
        <form method="GET" class="pr-search" role="search">
            @foreach (request()->only(['kind', 'county', 'market']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $q }}" placeholder="What do you need? e.g. plumber, braids" aria-label="Search professionals">
            <button type="submit">Search</button>
        </form>
    </header>

    <nav class="pr-kinds" aria-label="Kind of service">
        <a href="{{ $here(['kind' => null]) }}" @class(['is-active' => $category === ''])>All</a>
        @foreach (P::CATEGORIES as $k => [$label, $icon])
            <a href="{{ $here(['kind' => $k]) }}" @class(['is-active' => $category === $k])><i class="{{ $icon }}" aria-hidden="true"></i> {{ $label }}</a>
        @endforeach
    </nav>

    <form method="GET" class="pr-filters">
        @foreach (request()->only(['kind', 'q']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <label><span>County</span>
            <select name="county" onchange="this.form.submit()">
                <option value="">Anywhere</option>
                @foreach ($counties as $id => $name)<option value="{{ $id }}" @selected($county === (int) $id)>{{ $name }}</option>@endforeach
            </select>
        </label>
        <label><span>Market</span>
            <select name="market" onchange="this.form.submit()">
                <option value="">Any</option>
                @foreach ($markets as $id => $name)<option value="{{ $id }}" @selected($market === (int) $id)>{{ $name }}</option>@endforeach
            </select>
        </label>
        <noscript><button type="submit">Filter</button></noscript>
    </form>

    @if ($pros->isEmpty())
        <div class="pr-empty"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><b>No professionals here yet</b><span>Try another kind or county.@auth('customer') Or <a href="{{ route('hub.account.professional.index') }}">offer your own services</a>.@endauth</span></div>
    @else
        <ul class="pr-grid">
            @foreach ($pros as $p)
                @php $photo = P::mediaUrl($p->photo); @endphp
                <li>
                    <a href="{{ route('pros.show', $p->account) }}" class="pr-card">
                        <span class="pr-face">@if ($photo)<img src="{{ $photo }}" alt="" loading="lazy" onerror="this.remove()">@endif<i class="{{ P::CATEGORIES[$p->category][1] ?? 'fa-solid fa-briefcase' }}" aria-hidden="true"></i></span>
                        <b>{{ $p->display_name }}</b>
                        <small>{{ $p->profession }}</small>
                        <span class="pr-meta">
                            @if ($p->area || isset($counties[$p->county_id]))<em><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $p->area ?: $counties[$p->county_id] }}</em>@endif
                            @if ($p->done_jobs)<em><i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ $p->done_jobs }} {{ \Illuminate\Support\Str::plural('job', (int) $p->done_jobs) }} done</em>@endif
                        </span>
                        @if ($p->rate_from !== null)<strong>From KES {{ number_format((float) $p->rate_from) }}@if ($p->rate_unit)<small> / {{ $p->rate_unit }}</small>@endif</strong>@endif
                        @if ($p->featured)<span class="pr-top">Top</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($pros->hasPages())
            <nav class="pr-pages" aria-label="Pages">
                @if ($pros->previousPageUrl())<a href="{{ $pros->previousPageUrl() }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Previous</a>@endif
                <span>Page {{ $pros->currentPage() }} of {{ $pros->lastPage() }}</span>
                @if ($pros->nextPageUrl())<a href="{{ $pros->nextPageUrl() }}">Next <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>@endif
            </nav>
        @endif
    @endif

    @auth('customer')
        <a href="{{ route('hub.account.professional.index') }}" class="pr-join"><i class="fa-solid fa-briefcase" aria-hidden="true"></i><span><b>Are you a professional?</b><small>Create your profile and get booked.</small></span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
    @endauth
</div>
@endsection
