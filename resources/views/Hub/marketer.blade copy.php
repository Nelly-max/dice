@extends('layouts.hub')

@section('content')

<div class="hub-content hub-dash no-sidebar fullground">
    <div class="left">
        <div class="account-details">

            {{-- Header --}}
            <div class="account-detail top-area">
                <h4>Marketer Account</h4>

                {{-- Shortcuts --}}
                <div class="shortcuts">

                    <a
                        href="{{ route('hub.account.marketer.edit') }}"
                        class="shortcut"
                    >
                        <i class="fa-solid fa-user-pen"></i>
                        Edit
                    </a>

                    <a
                        href="{{ route('hub.account.marketer.join') }}"
                        class="shortcut"
                        style="color: #079d9f; background: #d7fdff;"
                    >
                        <i class="fa-solid fa-person-circle-plus"></i>
                        Join
                    </a>

                </div>
            </div>

            {{-- Account --}}
            <div class="account-detail">
                <h1 class="sub-heading">Account</h1>

                <span>
                    <h4>Account</h4>
                    <h3>{{ $customer->account ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Referral Code</h4>
                    <h3>{{ $marketer->referral_code ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Referral Link</h4>
                    <h3>{{ $marketer->url_link ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Discount</h4>
                    <h3>{{ $marketer->discount_percentage ?? 0 }}%</h3>
                </span>

                <span>
                    <h4>Status</h4>
                    <h3>{{ $marketer->status ?? '-' }}</h3>
                </span>
            </div>

            {{-- Personal Details --}}
            <div class="account-detail">
                <h1 class="sub-heading">Personal Details</h1>

                <span>
                    <h4>Name</h4>
                    <h3>{{ $marketer->name ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Phone</h4>
                    <h3>{{ $marketer->phone ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Email</h4>
                    <h3>{{ $marketer->email ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Gender</h4>
                    <h3>{{ $marketer->gender ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Date Of Birth</h4>
                    <h3>
                        {{ $marketer->date_of_birth
                            ? \Carbon\Carbon::parse($marketer->date_of_birth)->format('d/m/Y')
                            : '-' }}
                    </h3>
                </span>
            </div>

            {{-- Payment Information --}}
            <div class="account-detail">
                <h4 class="sub-heading">Payment Information</h4>

                <span>
                    <h4>Mpesa Number</h4>
                    <h3>{{ $marketer->mpesa_number ?? '-' }}</h3>
                </span>
            </div>

        </div>
    </div>

    <div class="right">
    </div>
</div>

@endsection
