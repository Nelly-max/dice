@extends('layouts.hub')

@section('content')
<div class="hub-content no-sidebar">
    <section class="table-top hub-table-card">

        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">My referrals</h2>
                <span class="hub-count">{{ $referrals->count() }}</span>
            </div>

            <div class="right hub-table-tools">
                <div class="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" placeholder="Search business, code, status…" data-table-filter="referralsTable" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="hub-table-wrap">
            <table class="table hub-table" data-sortable>
                <thead>
                    <tr>
                        <th data-sort="text">ID</th>
                        <th data-sort="text">Subdivision</th>
                        <th data-sort="text">Business</th>
                        <th data-sort="text">Referral code</th>
                        <th data-sort="number" class="is-num">Percentage</th>
                        <th data-sort="date">Date</th>
                        <th data-sort="text">Status</th>
                    </tr>
                </thead>

                <tbody id="referralsTable">
                    @forelse ($referrals as $referral)
                        @php $refStatus = $referral->status ?? 'pending'; @endphp
                        <tr>
                            <td data-label="ID"><strong>{{ 'REF' . str_pad($referral->id, 3, '0', STR_PAD_LEFT) }}</strong></td>
                            <td data-label="Subdivision">{{ $referral->subdivision_name ?? 'Unknown subdivision' }}</td>
                            <td data-label="Business">{{ $referral->business_account ?? '—' }}</td>
                            <td data-label="Referral code"><code class="hub-code">{{ $referral->referral_code ?? '—' }}</code></td>
                            <td data-label="Percentage" class="is-num" data-value="{{ (float) ($referral->discount ?? 0) }}">
                                {{ number_format((float) ($referral->discount ?? 0), 2) }}%
                            </td>
                            <td data-label="Date" data-value="{{ $referral->created_at?->timestamp ?? 0 }}">{{ $referral->created_at?->format('d M Y') ?? '—' }}</td>
                            <td data-label="Status">
                                <span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($refStatus) }}">{{ ucfirst($refStatus) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="7">
                                <div class="hub-empty">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                                    <strong>No referrals yet</strong>
                                    <span>Businesses that sign up with your code will show up here.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    <tr class="hub-no-match" hidden>
                        <td colspan="7"><div class="hub-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><strong>No referrals match your search</strong><span>Try a business, code or status.</span></div></td>
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
