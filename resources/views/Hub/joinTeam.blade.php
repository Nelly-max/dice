@extends('layouts.hub')

@section('content')

    <div class="hub-content hub-dash no-sidebar fullground">

        <div class="left">

            <form method="POST" action="{{ route('hub.marketer.store') }}">
                @csrf

                <div class="account-details">

                    <div class="account-detail top-area">
                        <h4>Fill your personal details</h4>
                    </div>

                    {{-- Personal Details --}}
                    <div class="account-detail">

                        <h1 class="sub-heading">Personal Details</h1>

                        <span>
                            <h4>Name</h4>

                            <inv class="required-input">
                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="Your Name"
                                    required
                                >

                                <h6 class="required">*</h6>
                            </inv>

                            @error('name')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </span>

                        <span>
                            <h4>Phone</h4>

                            <inv class="required-input">
                                <input
                                    type="text"
                                    name="phone"
                                    class="mobile-number"
                                    value="{{ old('phone') }}"
                                    placeholder="eg. 0701234567"
                                    required
                                >

                                <h6 class="required">*</h6>
                            </inv>

                            @error('phone')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </span>

                        <span>
                            <h4>Email</h4>

                            <inv class="required-input">
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="eg. myname@gmail.com"
                                    required
                                >

                                <h6 class="required">*</h6>
                            </inv>

                            @error('email')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </span>

                        <span>
                            <h4>Gender</h4>

                            <inv class="grid-checker">

                                <div class="grid-check">
                                    <input
                                        type="radio"
                                        name="gender"
                                        value="Male"
                                        {{ old('gender') === 'Male' ? 'checked' : '' }}
                                        required
                                    >

                                    <h5>Male</h5>
                                </div>

                                <div class="grid-check">
                                    <input
                                        type="radio"
                                        name="gender"
                                        value="Female"
                                        {{ old('gender') === 'Female' ? 'checked' : '' }}
                                    >

                                    <h5>Female</h5>
                                </div>

                            </inv>

                            @error('gender')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </span>

                        <span>
                            <h4>Date Of Birth</h4>

                            <input
                                type="date"
                                name="date_of_birth"
                                class="custom-date-picker"
                                value="{{ old('date_of_birth') }}"
                            >

                            @error('date_of_birth')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </span>

                    </div>

                    {{-- Payment Information --}}
                    <div class="account-detail">

                        <h4 class="sub-heading">Payment Information</h4>

                        <span>
                            <h4>Mpesa Number</h4>

                            <inv class="required-input">
                                <input
                                    type="text"
                                    name="mpesa_number"
                                    class="mobile-number"
                                    value="{{ old('mpesa_number') }}"
                                    placeholder="Mpesa number"
                                    required
                                >

                                <h6 class="required">*</h6>
                            </inv>

                            @error('mpesa_number')
                                <small class="error">{{ $message }}</small>
                            @enderror
                        </span>

                    </div>

                </div>

                <div class="button submit-data">
                    <button type="submit" id="submit-application">
                        Join
                    </button>
                </div>

            </form>

        </div>

        <div class="right">
        </div>

    </div>

@endsection

