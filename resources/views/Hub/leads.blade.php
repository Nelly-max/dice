@extends('layouts.hub')

@section('content')
{{--
    A marketer's leads (Hub\LeadController@index): businesses they would like to bring onto
    Smart Market. They go to the admin team's Sales → Leads list, which follows them up;
    the stage here is the team's progress. ?status= filters the table.
--}}
@php
    $S = \App\Http\Controllers\Web\Hub\LeadController::STATUSES;
    $total = $counts->sum();
    $onboarded = (int) ($counts['onboarded'] ?? 0);
@endphp
<div class="hub-content no-sidebar">

    <section class="hd-stats fin-stats" aria-label="Leads">
        <div class="hd-stat">
            <span class="hd-stat-icon"><i class="fa-solid fa-store" aria-hidden="true"></i></span>
            <div><small>Leads added</small><strong>{{ $total }}</strong><em>Businesses you suggested</em></div>
        </div>
        <div class="hd-stat is-wait">
            <span class="hd-stat-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
            <div><small>Waiting</small><strong>{{ (int) ($counts['new'] ?? 0) + (int) ($counts['contacted'] ?? 0) }}</strong><em>Not decided yet</em></div>
        </div>
        <div class="hd-stat">
            <span class="hd-stat-icon"><i class="fa-regular fa-thumbs-up" aria-hidden="true"></i></span>
            <div><small>Interested</small><strong>{{ (int) ($counts['interested'] ?? 0) }}</strong><em>Want to join</em></div>
        </div>
        <div class="hd-stat is-good">
            <span class="hd-stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <div><small>Onboarded</small><strong>{{ $onboarded }}</strong><em>Now on Smart Market</em></div>
        </div>
        <p class="hd-stats-note">Add a business you know that could sell on Smart Market. Our team calls it and keeps the stage here up to date. When it joins, share your referral code with it so the business counts as your referral.</p>
    </section>

    <section class="table-top hub-table-card">
        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">{{ $status ? $S[$status][0] . ' leads' : 'My leads' }}</h2>
                <span class="hub-count">{{ $leads->count() }}</span>
            </div>

            <div class="right hub-table-tools">
                <nav class="ref-chips" aria-label="Show">
                    <a href="{{ route('hub.leads') }}" @class(['is-active' => $status === '']) @if ($status === '') aria-current="page" @endif>All <b>{{ $total }}</b></a>
                    @foreach ($S as $key => [$label])
                        @if (($counts[$key] ?? 0) || $status === $key)
                            <a href="{{ route('hub.leads', ['status' => $key]) }}" @class(['is-active' => $status === $key]) @if ($status === $key) aria-current="page" @endif>{{ $label }} <b>{{ $counts[$key] ?? 0 }}</b></a>
                        @endif
                    @endforeach
                </nav>
                <div class="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" placeholder="Search business, phone, town…" data-table-filter="leadsTable" autocomplete="off">
                </div>
                <a href="{{ route('hub.leads.create') }}" class="lead-add"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add lead</a>
            </div>
        </div>

        <div class="hub-table-wrap">
            <table class="table hub-table hub-table--full" data-sortable>
                <thead>
                    <tr>
                        <th data-sort="text">Business</th>
                        <th data-sort="text">Phone</th>
                        <th data-sort="text">Where</th>
                        <th data-sort="text">Market</th>
                        <th data-sort="date">Added</th>
                        <th data-sort="text">Stage</th>
                        <th class="is-action"><span class="sr-only">View</span></th>
                    </tr>
                </thead>
                <tbody id="leadsTable">
                    @forelse ($leads as $l)
                        @php $url = route('hub.leads.show', $l->id); $added = $l->created_at ? \Illuminate\Support\Carbon::parse($l->created_at) : null; @endphp
                        <tr class="hub-row-link" data-href="{{ $url }}">
                            <td data-label="Business">
                                <a class="hub-row-a" href="{{ $url }}"><strong>{{ $l->name }}</strong></a>
                                @if ($l->contact_name || $l->business_type)<small>{{ collect([$l->contact_name, $l->business_type])->filter()->implode(' · ') }}</small>@endif
                            </td>
                            <td data-label="Phone">{{ $l->phone }}@if ($l->phone_2)<small>{{ $l->phone_2 }}</small>@endif</td>
                            <td data-label="Where">{{ $l->where ?: '—' }}</td>
                            <td data-label="Market">{{ $l->market_name ?: 'Not sure' }}</td>
                            <td data-label="Added" data-value="{{ $added?->timestamp ?? 0 }}">{{ $added?->format('d M Y') ?? '—' }}</td>
                            <td data-label="Stage"><span class="hub-pill lead-pill lead-pill--{{ $l->status }}">{{ $S[$l->status][0] ?? ucfirst($l->status) }}</span></td>
                            <td class="is-action">
                                <a class="hub-row-go" href="{{ $url }}" aria-label="View {{ $l->name }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="7">
                                <div class="hub-empty">
                                    <i class="fa-solid fa-store" aria-hidden="true"></i>
                                    @if ($status === '')
                                        <strong>No leads yet</strong>
                                        <span><a href="{{ route('hub.leads.create') }}">Add the first business</a> you think should be on Smart Market.</span>
                                    @else
                                        <strong>No {{ strtolower($S[$status][0]) }} leads</strong>
                                        <span><a href="{{ route('hub.leads') }}">See all leads</a></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <tr class="hub-no-match" hidden>
                        <td colspan="7"><div class="hub-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><strong>No leads match your search</strong><span>Try a business name, phone or town.</span></div></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="hub-table-foot">
            <p class="hub-table-info" data-table-info="leadsTable">
                {{ $leads->count() }} {{ \Illuminate\Support\Str::plural('lead', $leads->count()) }}
            </p>
        </div>
    </section>
</div>
@endsection
