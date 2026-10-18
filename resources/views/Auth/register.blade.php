@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                    <span class="title">Registration</span>

                    <form action="{{ route('account.register.create') }}" method="POST">
                        @csrf

                        {{-- General Error Messages --}}
                        @if(session('register_error'))
                            <div class="form-error">
                                **{{ session('register_error') }}**
                            </div>
                        @endif



                        {{-- Name --}}
                        @error('name')
                            <div class="form-error">**{{ $message }}**</div>
                        @enderror
                        <div class="input-field">
                            <input
                                type="text"
                                name="name"
                                placeholder="Enter Name"
                                maxlength="20"
                                value="{{ old('name') }}"
                                class="@error('name') is-invalid @enderror"
                                autocomplete="name"
                            >
                            <i class="uil uil-house-user"></i>
                        </div>


                        {{-- Email --}}

                        @error('email')
                            <div class="form-error">**{{ $message }}**</div>
                        @enderror
                        <div class="input-field">
                            <input
                                type="email"
                                name="email"
                                placeholder="Enter Email"
                                value="{{ old('email') }}"
                                class="@error('email') is-invalid @enderror"
                                autocomplete="email"
                            >
                            <i class="uil uil-envelope icon"></i>
                        </div>


                        {{-- Mobile Number --}}

                        @error('phone_number')
                            <div class="form-error">**{{ $message }}**</div>
                        @enderror
                        <div class="input-field">
                            <input
                                type="text"
                                name="phone_number"
                                placeholder="Mobile Number"
                                value="{{ old('phone_number') }}"
                                class="@error('phone_number') is-invalid @enderror"
                                autocomplete="tel"
                            >
                            <i class="uil uil-phone"></i>
                        </div>

                        @error('gender')
                            <div class="form-error">**{{ $message }}**</div>
                        @enderror
                        {{-- Gender --}}
                        <div class="input-radio">
                            <span>
                                <input
                                    type="radio"
                                    name="gender"
                                    id="genderMale"
                                    value="male"
                                    {{ old('gender') === 'male' ? 'checked' : '' }}
                                >
                                <label for="genderMale">Male</label>
                            </span>

                            <span>
                                <input
                                    type="radio"
                                    name="gender"
                                    id="genderFemale"
                                    value="female"
                                    {{ old('gender') === 'female' ? 'checked' : '' }}
                                >
                                <label for="genderFemale">Female</label>
                            </span>
                        </div>

                        @error('password')
                            <div class="form-error">**{{ $message }}**</div>
                        @enderror
                        {{-- Password --}}
                        <div class="input-field">
                            <input
                                type="password"
                                name="password"
                                class="password"
                                placeholder="Password"
                                autocomplete="new-password"
                            >
                            <i class="uil uil-lock icon"></i>
                            <i class="uil uil-eye-slash showHidePw"></i>
                        </div>

                        @error('password_confirmation')
                            <div class="form-error">**{{ $message }}**</div>
                        @enderror
                        {{-- Confirm Password --}}
                        <div class="input-field">
                            <input
                                type="password"
                                name="password_confirmation"
                                class="password"
                                placeholder="Confirm Password"
                                autocomplete="new-password"
                            >
                            <i class="uil uil-lock icon"></i>
                            <i class="uil uil-eye-slash showHidePw"></i>
                        </div>

                        {{-- Laravel's confirmed validation error is attached to password --}}
                        @if($errors->has('password') && str_contains(strtolower($errors->first('password')), 'confirmation'))
                            <div class="form-error">
                                {{ $errors->first('password') }}
                            </div>
                        @endif



                        {{-- Submit --}}
                        <div class="input-field button">
                            <input
                                type="submit"
                                value="Create Account"
                                class="btn-submit"
                            >
                        </div>
                    </form>

                    <div class="login-signup">
                        <span class="text">
                            Has an Account?
                            <a href="{{ route('login') }}" class="text signup-link">
                                Sign in now
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection