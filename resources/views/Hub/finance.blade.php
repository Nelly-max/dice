@extends('layouts.hub')

@section('content')

    <div class="hub-content no-sidebar fullground">
        <div class="wallet">
            <div class="wallet-head">
                <h1>Accounts</h1>
                <div class="wallet-options">
                    <div class="wallet-option">
                        <span><i class='bx bx-git-branch'></i><h6>Comission</h6></span>
                        <h2>Ksh 30000</h2>
                        <h5>RCG001A</h5>
                    </div>
                    <!-- <div class="wallet-option">
                        <span><i class='bx bx-git-branch'></i><h6>sub</h6></span>
                        <h2>Ksh 3000</h2>
                        <h5>Kiserian - Branch</h5>
                    </div> -->
                </div>
            </div>
            <div class="wallet-body">
                <div class="wallet-buttons">
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
                            <h5>Ksh 800</h5>
                        </div>
                        <div class="activity pending">
                            <span>
                                <h5>Fund Disbursed for profile RCG00A - <label>pending</label></h5>
                                <h6>4 -May || 12:00AM</h6>
                            </span>
                            <h5>Ksh 800</h5>
                        </div>
                        <div class="activity failed">
                            <span>
                                <h5>Fund Disbursed for profile RCG00A - <label>failed</label></h5>
                                <h6>4 -May || 12:00AM</h6>
                            </span>
                            <h5>Ksh 800</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
