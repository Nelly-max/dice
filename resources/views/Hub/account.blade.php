@extends('layouts.hub')

@section('content')

<div class="hub-content hub-dash no-sidebar fullground">

    <div class="left">

        <div class="account-details">

            {{-- Account Header --}}
            <div class="account-detail top-area">

                <div class="top">

                    <div>

                        <div id="profile-container" class="profile">

                            <img
                                id="profile-pic"
                                src="{{ $customer->profile_image
                                    ? rtrim(config('app.media_url'), '/') . '/' . ltrim($customer->profile_image, '/')
                                    : rtrim(config('app.media_url'), '/') . '/media/img/Customer/Profiles/user.png' }}"
                                alt="Customer Profile"
                            >


                            <input
                                type="file"
                                name="profile"
                                id="fileInput"
                                accept="image/*"
                            >

                        </div>

                        <h3 class="heading">
                            {{ $customer->account }}
                        </h3>

                        <h4>Customer Account</h4>

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


                {{-- Shortcuts --}}
                <div class="shortcuts">

                    <a
                        href="{{ route('hub.account.marketer.index') }}"
                        class="shortcut"
                    >
                        <i class="fa-solid fa-user-pen"></i>
                        Marketer
                    </a>

                    <a
                        href="{{ route('hub.account.rider.index') }}"
                        class="shortcut"
                        style="color: #079d9f; background: #d7fdff;"
                    >
                        <i class="fa-solid fa-motorcycle"></i>
                        Rider
                    </a>

                    <a
                        href="{{ route('hub.account.finance') }}"
                        class="shortcut"
                        style="color: #9f6007; background: #fff0d7;"
                    >
                        <i class="fa-solid fa-coins"></i>
                        Finance
                    </a>

                </div>

            </div>


            {{-- Profile --}}
            <div class="account-detail">

                <div class="head-area">

                    <h2>Profile</h2>

                    <div></div>

                </div>


                {{-- Account --}}
                <span>

                    <h4>Account</h4>

                    <h3>
                        {{ $customer->account }}
                    </h3>

                </span>


                {{-- Name --}}
                <span>

                    <h4>Name</h4>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $customer->name) }}"
                        maxlength="50"
                        pattern="[A-Za-z0-9 ]{1,15}"
                        oninput="this.value = this.value.replace(/[^A-Za-z0-9 ]/g, '').slice(0, 15);"
                    >

                </span>


                {{-- Username --}}
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
                <span>

                    <h4>Email</h4>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $customer->email) }}"
                    >

                </span>


                {{-- Gender --}}
                <span>

                    <h4>Gender</h4>

                    <div class="grid-checker">

                        <div class="grid-check">

                            <input
                                type="radio"
                                name="gender"
                                value="male"
                                {{ old('gender', $customer->gender) === 'male' ? 'checked' : '' }}
                            >

                            <h5>Male</h5>

                        </div>


                        <div class="grid-check">

                            <input
                                type="radio"
                                name="gender"
                                value="female"
                                {{ old('gender', $customer->gender) === 'female' ? 'checked' : '' }}
                            >

                            <h5>Female</h5>

                        </div>

                    </div>

                    @error('gender')
                        <small class="error">
                            {{ $message }}
                        </small>
                    @enderror

                </span>


                {{-- Date Of Birth --}}

                <span>
                    <h4>Date Of Birth</h4>

                    <input
                        type="date"
                        name="date_of_birth"
                        value="{{ old('date_of_birth', $customer->date_of_birth) }}"
                        class="custom-date-picker"
                        max="{{ now()->subYears(18)->format('Y-m-d') }}"
                    >
                </span>



                <div class="button submit-data">

                    <button
                        type="submit"
                        id="submit-application"
                    >
                        Update
                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- Right --}}
    <div class="right">

        <div class="account-details">

            <div class="account-detail">

                <h4 class="sub-heading">
                    Applications
                </h4>


                <span>

                    <h4>Rider</h4>

                    <a href="#">
                        View
                    </a>

                </span>


                <span>

                    <h4>Status</h4>

                    <h3>
                        Pending
                    </h3>

                </span>

            </div>

        </div>

    </div>

</div>

@endsection

