@extends('layouts.hub')

@section('content')
{{-- Hub → Jobs (Hub\ProfessionalController@jobs): bookings customers made with the professional. New ones first. --}}
@php
    use App\Support\Professionals as P;
    $total = $counts->sum();
    $pill = ['new' => 'pending', 'accepted' => 'confirmed', 'declined' => 'rejected', 'done' => 'completed', 'cancelled' => 'cancelled'];
@endphp
<div class="hub-content no-sidebar">

    <section class="hd-stats fin-stats" aria-label="Jobs">
        <div class="hd-stat is-wait">
            <span class="hd-stat-icon"><i class="fa-solid fa-bell" aria-hidden="true"></i></span>
            <div><small>New</small><strong>{{ (int) ($counts['new'] ?? 0) }}</strong><em>Waiting for your answer</em></div>
        </div>
        <div class="hd-stat">
            <span class="hd-stat-icon"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span>
            <div><small>Accepted</small><strong>{{ (int) ($counts['accepted'] ?? 0) }}</strong><em>Coming up</em></div>
        </div>
        <div class="hd-stat is-good">
            <span class="hd-stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <div><small>Done</small><strong>{{ (int) ($counts['done'] ?? 0) }}</strong><em>Finished jobs</em></div>
        </div>
        <p class="hd-stats-note">
            @if ($pro->account_status === 'active')
                Customers book you from your page. Share it from <a href="{{ route('hub.account.professional.index') }}">Professional</a>: put the link in your TikTok or Instagram bio.
            @else
                Your profile is {{ strtolower(P::STATUSES[$pro->account_status][0] ?? $pro->account_status) }}, so customers can't book you right now. <a href="{{ route('hub.account.professional.index') }}">See why</a>.
            @endif
        </p>
    </section>

    <section class="table-top hub-table-card">
        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">{{ $status ? P::JOB_STATUSES[$status][0] . ' jobs' : 'Jobs' }}</h2>
                <span class="hub-count">{{ $jobs->count() }}</span>
            </div>
            <div class="right hub-table-tools">
                <nav class="ref-chips" aria-label="Show">
                    <a href="{{ route('hub.jobs') }}" @class(['is-active' => $status === '']) @if ($status === '') aria-current="page" @endif>All <b>{{ $total }}</b></a>
                    @foreach (P::JOB_STATUSES as $k => [$label])
                        @if (($counts[$k] ?? 0) || $status === $k)
                            <a href="{{ route('hub.jobs', ['status' => $k]) }}" @class(['is-active' => $status === $k]) @if ($status === $k) aria-current="page" @endif>{{ $label }} <b>{{ $counts[$k] ?? 0 }}</b></a>
                        @endif
                    @endforeach
                </nav>
                <div class="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" placeholder="Search customer, service, job…" data-table-filter="jobsTable" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="hub-table-wrap">
            <table class="table hub-table hub-table--full" data-sortable>
                <thead>
                    <tr>
                        <th data-sort="text">Job</th>
                        <th data-sort="text">Customer</th>
                        <th data-sort="date">For</th>
                        <th data-sort="number">Price</th>
                        <th data-sort="text">Status</th>
                        <th class="is-action"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody id="jobsTable">
                    @forelse ($jobs as $j)
                        @php $url = route('hub.jobs.show', $j->id); $when = $j->wanted_at ? \Illuminate\Support\Carbon::parse($j->wanted_at) : null; @endphp
                        <tr class="hub-row-link {{ $j->status === 'new' ? 'is-new' : '' }}" data-href="{{ $url }}">
                            <td data-label="Job">
                                <a class="hub-row-a" href="{{ $url }}"><strong>{{ $j->service_name ?: 'General booking' }}</strong></a>
                                <small>{{ $j->reference }}{{ $j->source && $j->source !== 'smartmarket' ? ' · via ' . (P::PLATFORMS[$j->source][0] ?? $j->source) : '' }}</small>
                            </td>
                            <td data-label="Customer">{{ $j->customer_name }}<small>{{ $j->customer_phone }}</small></td>
                            <td data-label="For" data-value="{{ $when?->timestamp ?? 0 }}">{{ $when?->format('D d M, H:i') ?? '—' }}<small>{{ $when?->diffForHumans() }}</small></td>
                            <td data-label="Price" data-value="{{ (float) $j->price }}">{{ $j->price !== null ? 'KES ' . number_format((float) $j->price) : 'To agree' }}</td>
                            <td data-label="Status"><span class="hub-pill hub-pill--{{ $pill[$j->status] ?? 'pending' }}">{{ P::JOB_STATUSES[$j->status][0] ?? ucfirst($j->status) }}</span></td>
                            <td class="is-action"><a class="hub-row-go" href="{{ $url }}" aria-label="Open {{ $j->reference }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="6">
                                <div class="hub-empty">
                                    <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                                    <strong>{{ $status ? 'No ' . strtolower(P::JOB_STATUSES[$status][0]) . ' jobs' : 'No jobs yet' }}</strong>
                                    <span>Bookings from your page show here.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr class="hub-no-match" hidden>
                        <td colspan="6"><div class="hub-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><strong>No jobs match your search</strong></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
