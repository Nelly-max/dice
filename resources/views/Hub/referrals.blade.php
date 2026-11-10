@extends('layouts.hub')

@section('content')
{{--
    Marketer referrals (MarketerController@referrals): businesses that signed up with the
    marketer's code, their plan and subscription payments, and the marketer's commission
    (first three monthly invoices: 30%, 20%, 20% — config smartmarket.marketer_commission_months).
    Summary: total, this week, pending and performance (activated ÷ all); ?show= filters the table.
--}}
@php
    $kes = fn ($n) => 'KES ' . number_format((float) $n, 2);
@endphp
<div class="hub-content no-sidebar">

    {{-- referral summary: performance = activated ÷ all referrals (orange under 60%, red under 40%) --}}
    @php
        $perf = $stats['performance'];
        $perfClass = $perf === null ? '' : ($perf < 40 ? 'is-low' : ($perf < 60 ? 'is-mid' : 'is-high'));
        $chips = ['all' => ['All', $stats['total']], 'week' => ['This week', $stats['week']], 'pending' => ['Pending', $stats['pending']], 'active' => ['Active', $stats['active']]];
    @endphp
    <section class="hd-stats fin-stats" aria-label="Referrals">
        <div class="hd-stat">
            <span class="hd-stat-icon"><i class="fa-solid fa-link" aria-hidden="true"></i></span>
            <div><small>Referrals</small><strong>{{ $stats['total'] }}</strong><em>{{ $kes($totals['earned']) }} commission earned</em></div>
        </div>
        <div class="hd-stat is-good">
            <span class="hd-stat-icon"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></span>
            <div><small>This week</small><strong>{{ $stats['week'] }}</strong><em>Since Monday</em></div>
        </div>
        <div class="hd-stat is-wait">
            <span class="hd-stat-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
            <div><small>Pending</small><strong>{{ $stats['pending'] }}</strong><em>Not activated yet</em></div>
        </div>
        <div @class(['hd-stat', 'ref-perf', $perfClass])>
            <span class="hd-stat-icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span>
            <div>
                <small>Performance</small>
                <strong>{{ $perf === null ? '—' : $perf . '%' }}</strong>
                <em>{{ $stats['active'] }} of {{ $stats['total'] }} activated</em>
                @if ($perf !== null)
                    <span class="ref-perf-bar" role="img" aria-label="{{ $perf }}% of referrals activated"><i style="width: {{ $perf }}%"></i></span>
                @endif
            </div>
        </div>
        <p class="hd-stats-note">A referral is activated once the business pays its subscription. You earn <b>30%</b> of its first month's subscription and <b>20%</b> of the second and third months.</p>
    </section>

    <section class="table-top hub-table-card">
        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">{{ $show === 'all' ? 'My referrals' : $chips[$show][0] . ' referrals' }}</h2>
                <span class="hub-count">{{ $referrals->count() }}</span>
            </div>

            <div class="right hub-table-tools">
                <nav class="ref-chips" aria-label="Show">
                    @foreach ($chips as $key => [$label, $n])
                        <a href="{{ route('hub.referrals', $key === 'all' ? [] : ['show' => $key]) }}" @class(['is-active' => $show === $key]) @if ($show === $key) aria-current="page" @endif>{{ $label }} <b>{{ $n }}</b></a>
                    @endforeach
                </nav>
                <div class="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" placeholder="Search business, code, status…" data-table-filter="referralsTable" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="hub-table-wrap">
            <table class="table hub-table hub-table--full" data-sortable>
                <thead>
                    <tr>
                        <th data-sort="text">Referral</th>
                        <th data-sort="text">Business</th>
                        <th data-sort="text">Plan</th>
                        <th data-sort="number" class="is-num">Paid</th>
                        <th data-sort="number" class="is-num">Commission</th>
                        <th data-sort="date">Joined</th>
                        <th data-sort="text">Status</th>
                        <th class="is-action"><span class="sr-only">View</span></th>
                    </tr>
                </thead>

                <tbody id="referralsTable">
                    @forelse ($referrals as $referral)
                        @php
                            $refStatus = !empty($referral->is_active) ? 'active' : 'pending';   // activated = the business has paid
                            $url = route('hub.referrals.show', $referral->id);
                            $ref = 'REF' . str_pad($referral->id, 3, '0', STR_PAD_LEFT);
                        @endphp
                        <tr class="hub-row-link" data-href="{{ $url }}">
                            <td data-label="Referral">
                                <a class="hub-row-a" href="{{ $url }}"><strong>{{ $ref }}</strong></a>
                                <small><code class="hub-code">{{ $referral->referral_code ?? '—' }}</code></small>
                            </td>
                            <td data-label="Business">
                                {{ $referral->business_name ?? '—' }}
                                <small>{{ $referral->business_account ?? '' }}@if ($referral->subdivision_name) · {{ $referral->subdivision_name }}@endif</small>
                            </td>
                            <td data-label="Plan">{{ $referral->plan ?? '—' }}</td>
                            <td data-label="Paid" class="is-num" data-value="{{ $referral->paid_total }}">{{ $kes($referral->paid_total) }}</td>
                            <td data-label="Commission" class="is-num" data-value="{{ $referral->commission_earned }}">
                                <strong class="hd-money">{{ $kes($referral->commission_earned) }}</strong>
                                @if ($referral->commission_pending > 0)
                                    <small>+ {{ $kes($referral->commission_pending) }} waiting</small>
                                @endif
                            </td>
                            <td data-label="Joined" data-value="{{ $referral->created_at?->timestamp ?? 0 }}">{{ $referral->created_at?->format('d M Y') ?? '—' }}</td>
                            <td data-label="Status">
                                <span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($refStatus) }}">{{ ucfirst($refStatus) }}</span>
                            </td>
                            <td class="is-action">
                                <a class="hub-row-go" href="{{ $url }}" aria-label="View referral {{ $ref }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="8">
                                <div class="hub-empty">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                                    @if ($show === 'all')
                                        <strong>No referrals yet</strong>
                                        <span>Businesses that sign up with your code will show up here.</span>
                                    @else
                                        <strong>No {{ strtolower($chips[$show][0]) }} referrals</strong>
                                        <span><a href="{{ route('hub.referrals') }}">See all referrals</a></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <tr class="hub-no-match" hidden>
                        <td colspan="8"><div class="hub-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><strong>No referrals match your search</strong><span>Try a business, code or status.</span></div></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="hub-table-foot">
            <p class="hub-table-info" data-table-info="referralsTable">
                {{ $referrals->count() }} {{ \Illuminate\Support\Str::plural('referral', $referrals->count()) }}
            </p>
        </div>
    </section>
</div>
@endsection
