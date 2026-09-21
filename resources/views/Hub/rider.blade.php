@extends('layouts.hub')

@section('content')

<div class="hub-content hub-dash no-sidebar fullground">

    @if($rider)

        <div class="left">
            <div class="account-details">

                {{-- Header --}}
                <div class="account-detail top-area">

                    <h4>Rider Account</h4>

                    {{-- Shortcuts --}}
                    <div class="shortcuts">

                        <a
                            href="{{ route('hub.account.rider.edit') }}"
                            class="shortcut"
                        >
                            <i class="fa-solid fa-user-pen"></i>
                            Edit
                        </a>

                    </div>

                </div>


                {{-- Account --}}
                <div class="account-detail">

                    <h1 class="sub-heading">Account</h1>

                    <span>
                        <h4>Account</h4>
                        <h3>
                            {{ $rider->rider_account ?? '—' }}
                        </h3>
                    </span>

                    <span>
                        <h4>Status</h4>
                        <h3>
                            {{ ucfirst($rider->account_status ?? '—') }}
                        </h3>
                    </span>

                </div>


                {{-- Personal Details --}}
                <div class="account-detail">

                    <h1 class="sub-heading">Personal Details</h1>

                    <span>
                        <h4>Name</h4>
                        <h3>
                            {{ $rider->name ?? '—' }}
                        </h3>
                    </span>

                    <span>
                        <h4>Phone</h4>
                        <h3>
                            {{ $rider->phone ?? '—' }}
                        </h3>
                    </span>

                    <span>
                        <h4>Email</h4>
                        <h3>
                            {{ $rider->email ?? '—' }}
                        </h3>
                    </span>

                    <span>
                        <h4>National ID</h4>
                        <h3>
                            {{ $rider->national_id ?? '—' }}
                        </h3>
                    </span>

                    <span>
                        <h4>Licence No.</h4>
                        <h3>
                            {{ $rider->license_number ?? '—' }}
                        </h3>
                    </span>

                    <span>
                        <h4>Date Of Birth</h4>
                        <h3>
                            {{ $rider->date_of_birth?->format('d M Y') ?? '—' }}
                        </h3>
                    </span>

                </div>


                {{-- Payment Information --}}
                <div class="account-detail">

                    <h4 class="sub-heading">
                        Payment Information
                    </h4>

                    <span>
                        <h4>Mpesa Number</h4>

                        <h3>
                            {{ $rider->mpesa_number ?? '—' }}
                        </h3>
                    </span>

                </div>

            </div>
        </div>


        <div class="right">
        </div>


    @else

        {{-- No Rider Account --}}
        <div class="no-account">

            <h3>Team</h3>

            <p>HUB \ RIDER</p>

            <img
                src="{{ asset('img/gif/rider.gif') }}"
                alt="No Account"
            >

            <h2>You do not have a rider account!</h2>

            <p>
                Click the link below to create a rider team account
            </p>

            <a
                href="{{ route('hub.account.rider-application') }}"
                class="btn-continue"
            >
                Join Team
            </a>

        </div>

    @endif

</div>

@endsection

