@extends('layouts.hub')

@section('content')
{{--
    Hub finance (Hub\FinanceController@index): what the signed-in rider and / or marketer has
    earned, what has been paid out (finance.commission_payouts, rider payout records) and what
    is payable. Someone who is both sees a tab for each.
--}}
@php
    $kes = fn ($n) => 'KES ' . number_format((float) $n, 2);
    $roles = array_keys(array_filter(['rider' => $rider, 'marketer' => $marketer]));
    $current = $tab && in_array($tab, $roles, true) ? $tab : ($roles[0] ?? null);
    $data = $current ? ${$current} : null;
    $payoutStatus = fn ($s) => match ($s) {
        'paid' => 'Paid',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'processing' => 'Sending',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        default => ucfirst((string) $s),
    };
@endphp
<div class="hub-content no-sidebar hd-page fin-page">

    <header class="fin-head">
        <div>
            <h1>Finance</h1>
            <p>
                @if ($current === 'rider') Your delivery earnings and the payouts sent to you.
                @elseif ($current === 'marketer') Your referral commission and the payouts sent to you.
                @else Earnings and payouts for riders and marketers.
                @endif
            </p>
        </div>

        @if (count($roles) > 1)
            <nav class="fin-tabs" aria-label="Account">
                <a href="{{ route('hub.account.finance', ['tab' => 'rider']) }}" @class(['is-active' => $current === 'rider']) @if ($current === 'rider') aria-current="page" @endif><i class="fa-solid fa-motorcycle" aria-hidden="true"></i> Rider</a>
                <a href="{{ route('hub.account.finance', ['tab' => 'marketer']) }}" @class(['is-active' => $current === 'marketer']) @if ($current === 'marketer') aria-current="page" @endif><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Marketer</a>
            </nav>
        @endif
    </header>

    @if (!$current)
        <section class="hub-table-card">
            <div class="hub-empty">
                <i class="fa-solid fa-wallet" aria-hidden="true"></i>
                <strong>Nothing to show yet</strong>
                <span>Join the team as a rider or a marketer to earn with Smart Market. Your earnings and payouts will show here.</span>
                <a class="hd-cta fin-join" href="{{ route('hub.account.join-team') }}"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Join the team</a>
            </div>
        </section>
    @else

        {{-- totals --}}
        @if ($current === 'rider')
            <section class="hd-stats fin-stats" aria-label="Summary">
                <div class="hd-stat">
                    <span class="hd-stat-icon"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i></span>
                    <div><small>Total deliveries</small><strong>{{ number_format($rider['count']) }}</strong><em>{{ $kes($rider['earned']) }} earned</em></div>
                </div>
                <div class="hd-stat is-good">
                    <span class="hd-stat-icon"><i class="fa-solid fa-sun" aria-hidden="true"></i></span>
                    <div><small>Today's earnings</small><strong>{{ $kes($rider['today']) }}</strong></div>
                </div>
                <div class="hd-stat is-bad">
                    <span class="hd-stat-icon"><i class="fa-solid fa-percent" aria-hidden="true"></i></span>
                    <div><small>{{ rtrim(rtrim(number_format($rider['deductionRate'], 2), '0'), '.') }}% deduction</small><strong>− {{ $kes($rider['deduction']) }}</strong></div>
                </div>
                <div class="hd-stat fin-due">
                    <span class="hd-stat-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
                    <div><small>Payable amount</small><strong>{{ $kes($rider['payable']) }}</strong></div>
                </div>
                <p class="hd-stats-note">
                    You earn the delivery fee of every delivery you complete; Smart Market keeps {{ rtrim(rtrim(number_format($rider['deductionRate'], 2), '0'), '.') }}%.
                    @if ($rider['paid'] > 0 || $rider['onTheWay'] > 0)
                        {{ $kes($rider['paid']) }} paid out so far{{ $rider['onTheWay'] > 0 ? ' and ' . $kes($rider['onTheWay']) . ' on the way' : '' }}.
                    @endif
                    @if ($rider['payTo']) Payouts go to <b>{{ $rider['payTo'] }}</b>. @endif
                </p>
            </section>
        @else
            <section class="hd-stats fin-stats" aria-label="Summary">
                <div class="hd-stat">
                    <span class="hd-stat-icon"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></span>
                    <div><small>This week's referrals</small><strong>{{ number_format($marketer['stats']['week']) }}</strong><em>{{ $marketer['stats']['total'] }} in total</em></div>
                </div>
                <div class="hd-stat is-good">
                    <span class="hd-stat-icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
                    <div><small>Commission earned</small><strong>{{ $kes($marketer['commission']) }}</strong></div>
                </div>
                <div class="hd-stat is-wait">
                    <span class="hd-stat-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
                    <div><small>Pending commission</small><strong>{{ $kes($marketer['pending']) }}</strong><em>Months 2 and 3 at 20%</em></div>
                </div>
                <div class="hd-stat fin-due">
                    <span class="hd-stat-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
                    <div><small>Payable amount</small><strong>{{ $kes($marketer['payable']) }}</strong></div>
                </div>
                <p class="hd-stats-note">
                    You earn 30% of a referred business's first month's subscription and 20% of the second and third months. Pending commission becomes payable as those businesses pay.
                    @if ($marketer['paid'] > 0 || $marketer['onTheWay'] > 0)
                        {{ $kes($marketer['paid']) }} paid out so far{{ $marketer['onTheWay'] > 0 ? ' and ' . $kes($marketer['onTheWay']) . ' on the way' : '' }}.
                    @endif
                    @if ($marketer['payTo']) Payouts go to <b>{{ $marketer['payTo'] }}</b>. @endif
                    <a class="fin-more" href="{{ route('hub.referrals') }}">My referrals <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                </p>
            </section>
        @endif

        {{-- payouts --}}
        <section class="table-top hub-table-card">
            <div class="top hub-table-head">
                <div class="hub-table-title">
                    <h2 class="table_heading">{{ $current === 'rider' ? 'Rider payouts' : 'Commission payouts' }}</h2>
                    <span class="hub-count">{{ $data['payouts']->count() }}</span>
                </div>
            </div>

            <div class="hub-table-wrap">
                <table class="table hub-table hub-table--full" data-sortable>
                    <thead>
                        <tr>
                            <th data-sort="text">Reference</th>
                            <th data-sort="text">For</th>
                            <th data-sort="number" class="is-num">Amount</th>
                            <th data-sort="text">Status</th>
                            <th data-sort="date">Date</th>
                            <th data-sort="text">M-Pesa receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['payouts'] as $p)
                            @php $date = $p->date ? \Illuminate\Support\Carbon::parse($p->date) : null; @endphp
                            <tr>
                                <td data-label="Reference"><strong>{{ $p->reference }}</strong></td>
                                <td data-label="For">
                                    {{ $p->description }}
                                    @if (!empty($p->failure))<small class="fin-fail">{{ $p->failure }}</small>@endif
                                </td>
                                <td data-label="Amount" class="is-num" data-value="{{ $p->amount }}"><strong class="hd-money">{{ $kes($p->amount) }}</strong></td>
                                <td data-label="Status"><span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($p->status) }}">{{ $payoutStatus($p->status) }}</span></td>
                                <td data-label="Date" data-value="{{ $date?->timestamp ?? 0 }}">{{ $date ? $date->format($date->format('H:i') === '00:00' ? 'd M Y' : 'd M Y, H:i') : '—' }}</td>
                                <td data-label="M-Pesa receipt">@if ($p->receipt)<code class="hub-code">{{ $p->receipt }}</code>@else — @endif</td>
                            </tr>
                        @empty
                            <tr class="hub-empty-row">
                                <td colspan="6">
                                    <div class="hub-empty">
                                        <i class="fa-solid fa-money-bill-transfer" aria-hidden="true"></i>
                                        <strong>No payouts yet</strong>
                                        <span>Payouts sent to you will show here with their M-Pesa receipt.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    @endif
</div>
@endsection
