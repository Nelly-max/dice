@extends('layouts.hub')

@section('content')

<div class="hub-content hub-dash no-sidebar fullground">

@if($marketer)

    <div class="left">
        <div class="account-details">

            {{-- Header --}}
            <div class="account-detail top-area">
                <h4>Marketer Account</h4>

                {{-- Shortcuts --}}
                <div class="shortcuts">

                    @include('partials.Hub.back', ['fallback' => route('hub.account.index'), 'class' => 'shortcut'])

                    <a
                        href="{{ route('hub.account.marketer.edit') }}"
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
                    <h3>{{ $customer->account ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Referral Code</h4>
                    <h3>{{ $marketer->referral_code ?? '-' }}</h3>
                </span>

                <span>
                    <h4>Referral Link</h4>
                    @php $refUrl = Str::startsWith($marketer->url_link ?? '', ['http://', 'https://']) ? $marketer->url_link : 'https://' . $marketer->url_link; @endphp
                    {{-- tap: share sheet (WhatsApp, Instagram, TikTok, … copy link) with an "Open link" option --}}
                    <a href="{{ $refUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-share data-share-open
                    data-share-title="Join Smart Market"
                    data-share-text="Register your business on Smart Market with my referral link"
                    data-share-url="{{ $refUrl }}">
                        {{ $marketer->url_link ?: '-' }}
                    </a>
                </span>

                <!-- <span>
                    <h4>Discount</h4>
                    <h3>{{ $marketer->discount_percentage ?? 0 }}%</h3>
                </span> -->

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
                            ? $marketer->date_of_birth->format('d/m/Y')
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

    @else

        {{-- No Marketer Account --}}
        <div class="no-account">

            {{-- back to where they came from (My account if there is no history) --}}
            <a href="{{ route('hub.account.index') }}" class="no-account-back"
               onclick="if (history.length > 1) { history.back(); return false; }">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
            </a>

            <h3>Team</h3>

            <p>HUB \ MARKETER</p>

            <img
                src="{{ asset('img/gif/marketer.gif') }}"
                alt="No Account"
            >

            <h2>You do not have a marketer account!</h2>

            <p>
                Click the link below to create a marketer team account
            </p>

            <a
                href="{{ route('hub.account.marketer.join') }}"
                class="btn-continue"
            >
                Join Team
            </a>

        </div>

    @endif


</div>

@endsection
