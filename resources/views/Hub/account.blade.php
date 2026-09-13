@extends('layouts.hub')

@section('content')

    <div class="hub-content hub-dash no-sidebar fullground">
        <div class="left">
            <div class="account-details">
                <div class="account-detail top-area">
                    <div class="top">
                        <div>
                            <img src="../public/img/user.png" alt="">
                            <h3 class="heading">U01A-A001AZ</h3>
                            <h4>Standard Account</h4>
                        </div>
                        <div>
                            <div class="wallet-area">
                                <i class="fa-solid fa-wallet"></i>
                                <h4>Rider Payout</h4>
                                <h3>Ksh2000</h3>
                            </div>
                            <div class="wallet-area">
                                <i class="fa-solid fa-wallet"></i>
                                <h4>Commission</h4>
                                <h3>Ksh2500</h3>
                            </div>
                        </div>
                    </div>

                    <div class="shortcuts">
                        <a href="{{ route('hub.account.marketer.join') }}" class="shortcut">
                            <i class="fa-solid fa-user-pen"></i>
                            Join Marketers
                        </a>
                        <a href="{{ route('hub.account.rider-application') }}" class="shortcut" style="color: #079d9f; background: #d7fdff;">
                            <i class="fa-solid fa-motorcycle"></i>
                            Rider Application
                        </a>
                        <a class="shortcut" style="color: #9f6007; background: #fff0d7;">
                            <i class="fa-solid fa-coins"></i>
                            Finance
                        </a>
                        <!-- <button class="shortcut" style="color: #079f4e; background: #d7ffe4;">
                            <i class="fa-solid fa-sack-dollar"></i>
                            to M-pesa
                        </button> -->
                    </div>
                </div>

                <!-- <h1 class="sub-heading">Profile</h1> -->
                <div class="account-detail">
                    <div class="head-area">
                        <h2>Profile</h2>
                        <div>
                            <!-- <a href="accountEdit.html">
                                <i class="fa-solid fa-pencil"></i>
                                <h2>Edit</h2>
                            </a> -->
                        </div>
                    </div>
                    <span>
                        <h4>Name</h4>
                        <input type="text" value="Kelvin Kimani">
                    </span>
                    <span>
                        <h4>Username</h4>
                        <input type="text" value="Infinity">
                    </span>
                    <span>
                        <h4>Phone</h4>
                        <input type="text" value="0708275546">
                    </span>
                    <span>
                        <h4>Email</h4>
                        <input type="text" value="kkimani@gmail.email">
                    </span>
                    <span>
                        <h4>Gender</h4>
                        <div class="none-text-input">
                            <span>
                                <input type="radio">
                                <h3>Male</h3>
                            </span>
                            <span>
                                <input type="radio">
                                <h3>Female</h3>
                            </span>
                        </div>
                    </span>
                    <span>
                        <h4>ID Number</h4>
                        <input type="number" value="32456278">
                    </span>

                    <span>
                        <h4>Date Of Birth</h4>
                        <input type="date" value="1993-04-02">
                    </span>

                </div>
            </div>
        </div>
        <div class="right">
            <div class="account-details">
                <div class="account-detail">
                    <h4 class="sub-heading">Applications</h4>
                    <span>
                        <h4>Points Balance</h4>
                        <h3>Point 2</h3>
                    </span>
                    <span>
                        <h4>Amount</h4>
                        <h3>Ksh 0.20</h3>
                    </span>
                </div>

                <div class="account-detail">
                    <h4 class="sub-heading">Membership</h4>
                    
                </div>

                <div class="account-detail">
                    <h1 class="sub-heading">Professions</h1>

                </div>
            </div>
        </div>
    </div>

    @endsection
    <!-- @include('toast.toast') -->


<!-- <div class="toast notificationAlert" id="accept">

    <div class="bottom-toast no-sidebar">

        <div class="bottom-toast-data">

            <div class="btns">

                <button class="btn btn-danger deliveryAcceptBtn" id="acceptDeliveryBtn">

                    <div class="acceptProgress" id="acceptProgress"></div>

                    <h4 id="pickupStore">
                        Pickup Store:
                    </h4>

                    <i class="fa-regular fa-circle-check"></i>

                    <span id="acceptCountdown">
                        Accept Delivery (30)
                    </span>

                </button>

            </div>

        </div>

    </div>

</div> -->
        
