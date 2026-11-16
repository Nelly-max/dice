@extends('layouts.hub')

@section('content')
{{-- One referral (MarketerController@referral): the business, its subscription invoices and the commission on each. --}}
@php
    $kes = fn ($n) => 'KES ' . number_format((float) $n, 2);
    $ref = 'REF' . str_pad($referral->id, 3, '0', STR_PAD_LEFT);
    $refStatus = $referral->status ?? 'pending';
    $pct = rtrim(rtrim(number_format($rate, 2), '0'), '.');
@endphp
<div class="hub-content no-sidebar hd-page">

    <a href="{{ route('hub.referrals') }}" class="hd-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> My referrals</a>

    <header class="hd-head">
        <span class="hd-head-icon"><i class="fa-solid fa-store" aria-hidden="true"></i></span>
        <div>
            <h1>{{ $referral->business_name ?? $referral->business_account }}</h1>
            <p>{{ $ref }} · {{ $referral->subdivision_name ?? 'Unknown subdivision' }} · joined {{ $referral->created_at?->format('d M Y') ?? '—' }}</p>
        </div>
        <span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($refStatus) }}">{{ ucfirst($refStatus) }}</span>
    </header>

    <section class="hd-stats" aria-label="Commission">
        <div class="hd-stat is-good">
            <span class="hd-stat-icon"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></span>
            <div><small>Your commission</small><strong>{{ $kes($referral->commission_earned) }}</strong></div>
        </div>
        <div class="hd-stat">
            <span class="hd-stat-icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></span>
            <div><small>Subscription paid</small><strong>{{ $kes($referral->paid_total) }}</strong></div>
        </div>
        <div class="hd-stat is-wait">
            <span class="hd-stat-icon"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
            <div><small>Waiting on payment</small><strong>{{ $kes($referral->commission_pending) }}</strong></div>
        </div>
    </section>

    <div class="hd-grid">
        <section class="hub-card">
            <h2 class="hub-card-title">Referral</h2>
            <dl class="hd-list">
                <div><dt>Business account</dt><dd>{{ $referral->business_account ?? '—' }}</dd></div>
                <div><dt>Subdivision</dt><dd>{{ $referral->subdivision_name ?? '—' }}</dd></div>
                <div><dt>Referral code</dt><dd><code class="hub-code">{{ $referral->referral_code ?? '—' }}</code></dd></div>
                <div><dt>Business discount</dt><dd>{{ number_format((float) ($referral->discount ?? 0), 2) }}%</dd></div>
                <div><dt>Plan</dt><dd>{{ $referral->plan ?? '—' }}</dd></div>
                <div><dt>Subscription</dt><dd>
                    @if ($referral->subscription_status)
                        <span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($referral->subscription_status) }}">{{ ucfirst($referral->subscription_status) }}</span>
                    @else — @endif
                </dd></div>
                <div><dt>Last payment</dt><dd>{{ $referral->last_payment_at ? \Illuminate\Support\Carbon::parse($referral->last_payment_at)->format('d M Y') : '—' }}</dd></div>
            </dl>
        </section>

        <section class="hub-card">
            <h2 class="hub-card-title">How your commission works</h2>
            <p class="hd-text">
                You earn commission on this business's first three months of subscription:
                <b>30%</b> of the first month and <b>20%</b> of the second and third.
                Commission on an unpaid month shows as <em>waiting</em> and moves to <em>earned</em> once the business pays.
            </p>
            <ol class="hd-months">
                @foreach ([1 => 30, 2 => 20, 3 => 20] as $m => $p)
                    @php $inv = $referral->invoices->firstWhere('month', $m); @endphp
                    <li @class(['is-paid' => $inv && $inv->status === 'paid', 'is-wait' => $inv && $inv->status !== 'paid'])>
                        <span>Month {{ $m }} · {{ $p }}%</span>
                        <b>{{ $inv ? $kes($inv->commission) : 'Not invoiced yet' }}</b>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <section class="table-top hub-table-card">
        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">First three months</h2>
                <span class="hub-count">{{ $referral->invoices->count() }}</span>
            </div>
        </div>
        <div class="hub-table-wrap">
            <table class="table hub-table hub-table--full">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Invoice</th>
                        <th>Plan</th>
                        <th>Period</th>
                        <th class="is-num">Amount</th>
                        <th class="is-num">Rate</th>
                        <th class="is-num">Your commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($referral->invoices as $invoice)
                        @php $paid = $invoice->status === 'paid'; @endphp
                        <tr>
                            <td data-label="Month"><strong>Month {{ $invoice->month }}</strong></td>
                            <td data-label="Invoice">#{{ $invoice->id }}<small>{{ $invoice->created_at ? \Illuminate\Support\Carbon::parse($invoice->created_at)->format('d M Y') : '' }}</small></td>
                            <td data-label="Plan">{{ $invoice->tariff_name ?? '—' }}</td>
                            <td data-label="Period">
                                @if ($invoice->starts_at)
                                    {{ \Illuminate\Support\Carbon::parse($invoice->starts_at)->format('d M') }} – {{ $invoice->expires_at ? \Illuminate\Support\Carbon::parse($invoice->expires_at)->format('d M Y') : '…' }}
                                @else — @endif
                            </td>
                            <td data-label="Amount" class="is-num">{{ $kes($invoice->amount) }}</td>
                            <td data-label="Rate" class="is-num">{{ rtrim(rtrim(number_format($invoice->rate, 2), '0'), '.') }}%</td>
                            <td data-label="Your commission" class="is-num"><strong @class(['hd-money' => $paid, 'hd-money-wait' => !$paid])>{{ $kes($invoice->commission) }}</strong></td>
                            <td data-label="Status"><span class="hub-pill hub-pill--{{ $paid ? 'paid' : 'pending' }}">{{ $paid ? 'Paid' : 'Unpaid' }}</span></td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="8">
                                <div class="hub-empty">
                                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                    <strong>No subscription invoices yet</strong>
                                    <span>Commission starts when this business pays for a plan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
