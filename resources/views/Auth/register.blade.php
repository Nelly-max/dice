@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                    <span class="title">Registration</span>
                    <p class="subtitle">Create an account to order from shops near you.</p>

                    <form action="{{ route('account.register.create') }}" method="POST" data-notify-loading="Creating your account…">
                        @csrf

                        {{-- General Error Messages --}}
                        @if(session('register_error'))
                            <div class="form-error">
                                {{ session('register_error') }}
                            </div>
                        @endif



                        {{-- Name --}}
                        @error('name')
                            <div class="form-error">{{ $message }}</div>
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
                            <i class="fa-regular fa-user icon"></i>
                        </div>


                        {{-- Email --}}

                        @error('email')
                            <div class="form-error">{{ $message }}</div>
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
                            <i class="fa-regular fa-envelope icon"></i>
                        </div>


                        {{-- Mobile Number --}}

                        @error('phone_number')
                            <div class="form-error">{{ $message }}</div>
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
                            <i class="fa-solid fa-phone icon"></i>
                        </div>

                        @error('gender')
                            <div class="form-error">{{ $message }}</div>
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
                                <label for="genderMale"><i class="fa-solid fa-mars" aria-hidden="true"></i> Male</label>
                            </span>

                            <span>
                                <input
                                    type="radio"
                                    name="gender"
                                    id="genderFemale"
                                    value="female"
                                    {{ old('gender') === 'female' ? 'checked' : '' }}
                                >
                                <label for="genderFemale"><i class="fa-solid fa-venus" aria-hidden="true"></i> Female</label>
                            </span>
                        </div>

                        {{-- Date of birth (optional) --}}
                        @error('date_of_birth')
                            <div class="form-error">{{ $message }}</div>
                        @enderror
                        <label class="field-label" for="dateOfBirth">Date of birth <span>(optional)</span></label>
                        <div class="input-field">
                            <input
                                type="date"
                                name="date_of_birth"
                                id="dateOfBirth"
                                value="{{ old('date_of_birth') }}"
                                max="{{ now()->subYears(10)->toDateString() }}"
                                min="1900-01-01"
                                class="@error('date_of_birth') is-invalid @enderror"
                                autocomplete="bday"
                            >
                            <i class="fa-regular fa-calendar icon"></i>
                        </div>

                        @error('password')
                            <div class="form-error">{{ $message }}</div>
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
                            <i class="fa-solid fa-lock icon"></i>
                            <i class="fa-regular fa-eye-slash showHidePw"></i>
                        </div>

                        @error('password_confirmation')
                            <div class="form-error">{{ $message }}</div>
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
                            <i class="fa-solid fa-lock icon"></i>
                            <i class="fa-regular fa-eye-slash showHidePw"></i>
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
                            <a href="{{ route('login') }}" class="text">
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