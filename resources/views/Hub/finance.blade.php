@extends('layouts.hub')

@section('content')

    <div class="hub-content no-sidebar fullground">
        <div class="wallet">
            <div class="wallet-head">
                <h1>Accounts</h1>
                @php
                    $financeCustomerId = auth('customer')->id();
                    $financeMarketer = $financeCustomerId ? \App\Models\Hub\Marketer::where('customer_id', $financeCustomerId)->first() : null;
                    $financeRider = $financeCustomerId ? \App\Models\Hub\Rider::where('customer_id', $financeCustomerId)->first() : null;
                @endphp
                <div class="wallet-options">
                    {{-- one tile per team account: marketer (referral code) and/or rider (rider account) --}}
                    @if ($financeMarketer)
                        <div class="wallet-option">
                            <span><i class='bx bx-git-branch'></i><h6>Commission</h6></span>
                            <h2>Ksh 0</h2>
                            <h5>{{ $financeMarketer->referral_code ?? '-' }}</h5>
                        </div>
                    @endif
                    @if ($financeRider)
                        <div class="wallet-option">
                            <span><i class="fa-solid fa-motorcycle"></i><h6>Rider payout</h6></span>
                            <h2>Ksh 0</h2>
                            <h5>{{ $financeRider->rider_account ?? '-' }}</h5>
                        </div>
                    @endif
                    @if (!$financeMarketer && !$financeRider)
                        <div class="wallet-option">
                            <span><i class='bx bx-git-branch'></i><h6>Commission</h6></span>
                            <h2>Ksh 0</h2>
                            <h5>Join the team to earn</h5>
                        </div>
                    @endif
                    <!-- <div class="wallet-option">
                        <span><i class='bx bx-git-branch'></i><h6>sub</h6></span>
                        <h2>Ksh 3000</h2>
                        <h5>Kiserian - Branch</h5>
                    </div> -->
                </div>
            </div>
            <div class="wallet-body">
                <div class="wallet-buttons">
                    @include('partials.Hub.back', ['fallback' => route('hub.account.index')])
                    <button><i class="fa-solid fa-coins"></i>M-pesa</button>
                </div>

                <div class="recents">
                    <h4>Recents</h4>
                    <div class="activities">
                        <div class="activity succcess">
                            <span>
                                <h5>Fund Disbursed for profile RCG00A - <label>sucess</label></h5>
                                <h6>4 -May || 12:00AM</h6>
                            </span>
                            <h5>Ksh800</h5>
                        </div>
                        <div class="activity pending">
                            <span>
                                <h5>Fund Disbursed for profile RCG00A - <label>pending</label></h5>
                                <h6>4 -May || 12:00AM</h6>
                            </span>
                            <h5>Ksh800</h5>
                        </div>
                        <div class="activity failed">
                            <span>
                                <h5>Fund Disbursed for profile RCG00A - <label>failed</label></h5>
                                <h6>4 -May || 12:00AM</h6>
                            </span>
                            <h5>Ksh800</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
