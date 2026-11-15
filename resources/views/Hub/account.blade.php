@extends('layouts.hub')

@section('content')

<div class="hub-content hub-form hub-account hub-dash no-sidebar fullground">

    {{-- Top, across the page: who you are, payouts and the role shortcuts --}}
    <div class="acct-top">

        <div class="account-details">

            {{-- Account Header --}}
            @php
                $rider = $rider ?? null;
                $marketer = $marketer ?? null;
                $wallets = $wallets ?? ['rider' => null, 'marketer' => null];
                $hasRiderAccount = (bool) $rider;
                $hasMarketerAccount = (bool) $marketer;
                $ksh = fn ($v) => 'Ksh ' . number_format((float) $v, ((float) $v == floor((float) $v)) ? 0 : 2);
                $avatar = $customer->profile_image
                    ? rtrim(config('app.media_url'), '/') . '/' . ltrim($customer->profile_image, '/')
                    : rtrim(config('app.media_url'), '/') . '/img/Customer/Profiles/user.png';
            @endphp
            <section class="account-detail acct-hero" aria-label="Your account">

                <div class="acct-hero-main">

                    {{-- tap the photo to change it: it saves straight away (ProfileController::updatePhoto) --}}
                    <form action="{{ route('hub.account.photo') }}" method="POST" enctype="multipart/form-data" class="acct-avatar-form" id="hubPhotoForm">
                        @csrf
                        <div id="profile-container" class="acct-avatar" title="Change your photo" role="button" tabindex="0" aria-label="Change your profile photo">
                            <img id="profile-pic" src="{{ $avatar }}" alt="Your profile photo">
                            <input type="file" name="profile" id="fileInput" accept="image/jpeg,image/png,image/webp">
                            <i class="fa-solid fa-camera acct-avatar-edit" aria-hidden="true"></i>
                            <i class="fa-solid fa-circle-notch fa-spin acct-avatar-busy" aria-hidden="true"></i>
                        </div>
                        @error('profile')
                            <b class="acct-avatar-error">{{ $message }}</b>
                        @enderror
                    </form>

                    <div class="acct-id">
                        <h2>{{ $customer->name ?: 'My account' }}</h2>
                        <div class="acct-chips">
                            <b class="acct-chip"><i class="fa-regular fa-id-card" aria-hidden="true"></i> {{ $customer->account }}</b>
                            <b class="acct-chip is-customer"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i> Customer</b>
                            @if ($hasRiderAccount)
                                <b class="acct-chip is-rider"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i> Rider{{ $rider->account_status && $rider->account_status !== 'active' ? ' · ' . ucfirst($rider->account_status) : '' }}</b>
                            @endif
                            @if ($hasMarketerAccount)
                                <b class="acct-chip is-marketer"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i> Marketer</b>
                            @endif
                        </div>
                    </div>

                </div>

                @if ($hasRiderAccount || $hasMarketerAccount)
                    {{-- approved disbursements (finance.commission_payouts: approved, being sent, paid) --}}
                    <div class="acct-wallets">
                        @foreach (['rider' => ['Delivery payouts', 'fa-solid fa-motorcycle'], 'marketer' => ['Commission payouts', 'fa-solid fa-bullhorn']] as $type => [$label, $icon])
                            @continue(!$wallets[$type])
                            @php $w = $wallets[$type]; @endphp
                            <a href="{{ route('hub.account.finance') }}" class="acct-wallet is-{{ $type }}">
                                <i class="{{ $icon }}" aria-hidden="true"></i>
                                <div>
                                    <small>{{ $label }}</small>
                                    <strong>{{ $ksh($w['approved']) }}</strong>
                                    <em>
                                        @if ($w['approved'] <= 0)
                                            Nothing approved yet
                                        @else
                                            {{ $ksh($w['paid']) }} paid{{ $w['onTheWay'] > 0 ? ' · ' . $ksh($w['onTheWay']) . ' on the way' : '' }}
                                        @endif
                                    </em>
                                </div>
                                <i class="fa-solid fa-chevron-right acct-wallet-go" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Shortcuts --}}
                <nav class="acct-shortcuts" aria-label="Your roles">
                    <a href="{{ route('hub.account.marketer.index') }}" class="acct-shortcut is-marketer">
                        <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                        <div><b>Marketer</b><small>{{ $hasMarketerAccount ? 'Your referral code and profile' : 'Earn by bringing businesses' }}</small></div>
                    </a>
                    <a href="{{ route('hub.account.rider.index') }}" class="acct-shortcut is-rider">
                        <i class="fa-solid fa-motorcycle" aria-hidden="true"></i>
                        <div><b>Rider</b><small>{{ $hasRiderAccount ? 'Your rider profile and status' : 'Deliver and get paid' }}</small></div>
                    </a>
                    @if ($hasRiderAccount || $hasMarketerAccount)
                        <a href="{{ route('hub.account.finance') }}" class="acct-shortcut is-finance">
                            <i class="fa-solid fa-coins" aria-hidden="true"></i>
                            <div><b>Finance</b><small>Payouts and statements</small></div>
                        </a>
                    @endif
                </nav>

            </section>

        </div>

    </div>


    {{-- Left: the profile form --}}
    <div class="left">

        <div class="account-details">

            {{-- Profile --}}


            <form action="{{ route('hub.account.update') }}" method="POST" id="hubProfileForm">
                @csrf
                @method('PUT')

                <div class="account-detail">

                    <div class="head-area">
                        <h2>Profile</h2>
                        <div></div>
                    </div>

                    {{-- Success / error messages appear as toasts (partials/notify-flash) --}}

                    {{-- Account --}}
                    <span>
                        <h4>Account</h4>
                        <h3>{{ $customer->account }}</h3>
                    </span>

                    {{-- Name --}}
                    
                    @error('name')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                    <span>
                        <h4>Name</h4>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $customer->name) }}"
                            maxlength="15"
                            pattern="[A-Za-z0-9 ]{1,15}"
                            title="Name can only contain letters, numbers and spaces, with a maximum of 15 characters."
                            oninput="this.value = this.value.replace(/[^A-Za-z0-9 ]/g, '').slice(0, 15);"
                        >

                    </span>

                    {{-- Username --}}
                    @error('username')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                    <span>
                        <h4>Username</h4>

                        <input
                            type="text"
                            name="username"
                            value="{{ old('username', $customer->username) }}"
                            maxlength="15"
                            pattern="[A-Za-z0-9 ]{1,15}"
                            title="Username can only contain letters, numbers and spaces, with a maximum of 15 characters."
                            oninput="this.value = this.value.replace(/[^A-Za-z0-9 ]/g, '').slice(0, 15);"
                        >
                    </span>

                    {{-- Phone --}}
                    @error('phone_number')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                    <span>
                        <h4>Phone</h4>

                        <input
                            type="text"
                            name="phone_number"
                            class="mobile-number"
                            value="{{ old('phone_number', $customer->phone_number) }}"
                        >
                    </span>

                    {{-- Email --}}
                    @error('email')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                    <span>
                        <h4>Email</h4>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $customer->email) }}"
                        >
                    </span>

                    {{-- Gender --}}
                    @error('gender')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                    <span>
                        <h4>Gender</h4>

                        <div class="grid-checker">

                            <label class="grid-check">
                                <input
                                    type="radio"
                                    name="gender"
                                    value="male"
                                    {{ old('gender', $customer->gender) === 'male' ? 'checked' : '' }}
                                >
                                <h5>Male</h5>
                            </label>

                            <label class="grid-check">
                                <input
                                    type="radio"
                                    name="gender"
                                    value="female"
                                    {{ old('gender', $customer->gender) === 'female' ? 'checked' : '' }}
                                >
                                <h5>Female</h5>
                            </label>

                        </div>
                    </span>

                    {{-- Date Of Birth --}}
                    @error('date_of_birth')
                        <small class="form-error">{{ $message }}</small>
                    @enderror
                    <span>
                        <h4>Date Of Birth</h4>

                        <input
                            type="date"
                            name="date_of_birth"
                            value="{{ old('date_of_birth', $customer->date_of_birth ? \Illuminate\Support\Carbon::parse($customer->date_of_birth)->format('Y-m-d') : '') }}"
                            class="custom-date-picker"
                            max="{{ now()->subYears(18)->format('Y-m-d') }}"
                        >


                    </span>

                    {{-- Submit --}}
                    <div class="button submit-data">
                        <button type="submit">Update Profile</button>
                    </div>

                </div>
            </form>

        </div>

    </div>


    {{-- Right: the rider application while it is under review, not approved or on hold
         (partials/Hub/application-status). Approved riders don't see it. --}}
    @php $riderApp = $rider ?? null; @endphp
    @if ($riderApp && $riderApp->account_status !== 'active')
    <div class="right">

        <div class="account-details">

            @include('partials.Hub.application-status', ['rider' => $riderApp])

        </div>

    </div>
    @endif

</div>

<script>
    // the header photo: pick one, it shows straight away and is saved (the page comes back with a toast)
    (() => {
        const form = document.getElementById('hubPhotoForm');
        const box = document.getElementById('profile-container');
        const input = document.getElementById('fileInput');
        if (!form || !box || !input) return;
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
        });
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;
            if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size > 5 * 1024 * 1024) {
                input.value = '';
                window.notify ? window.notify('Pick a JPG, PNG or WebP photo of 5 MB or less.', 'error')
                              : alert('Pick a JPG, PNG or WebP photo of 5 MB or less.');
                return;
            }
            document.getElementById('profile-pic').src = URL.createObjectURL(file);
            box.classList.add('is-busy');
            form.submit();
        });
    })();
</script>
@endsection

