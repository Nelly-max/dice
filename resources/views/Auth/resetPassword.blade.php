@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                    <span class="title">Reset Your Password</span>

                    {{-- Success Message --}}
                    @if(session('success'))
                        <div class="text">
                            {{ session('success') }}
                        </div>
                    @endif

                    
                    @error('email')
                        <div class="form-error">
                            **{{ $message }}**
                        </div>
                    @enderror

                    <form action="{{ route('password.reset.request') }}" method="POST">
                        @csrf

                        {{-- Email --}}
                        <div class="input-field">
                            <input
                                type="email"
                                name="email"
                                placeholder="Enter your email"
                                value="{{ old('email') }}"
                                class="@error('email') is-invalid @enderror"
                                autocomplete="email"
                            >
                            <i class="uil uil-envelope icon"></i>
                        </div>


                        <div class="checkbox-text">
                            <span class="text">
                                A six-digit OTP will be sent to your email
                                to help you reset your password.
                            </span>
                        </div>

                        <div class="login-signup">
                            <span class="text">
                                Remembered your password?
                                <a href="{{ route('login') }}" class="text">
                                    Login now
                                </a>
                            </span>
                        </div>

                        {{-- Submit --}}
                        <div class="input-field button">
                            <input
                                type="submit"
                                value="Reset"
                            >
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection