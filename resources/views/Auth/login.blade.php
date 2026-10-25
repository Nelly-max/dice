@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                    <span class="title">Log In</span>
                    <p class="subtitle">Welcome back. Sign in to keep shopping.</p>

                    <form action="{{ route('account.login') }}" method="POST" data-notify-loading="Signing you in…">
                        @csrf

                        {{-- e.g. "Account created successfully! Your Account Number is ..." --}}
                        @if(session('success'))
                            <div class="form-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{-- Login Error Messages --}}
                        @if(session('login_error'))
                            <div class="form-error">
                                {{ session('login_error') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="form-error">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        {{-- Account or Email --}}
                        <div class="input-field">
                            <input
                                type="text"
                                name="login_email"
                                placeholder="Enter Account / Email"
                                value="{{ old('login_email') }}"
                                class="@error('login_email') is-invalid @enderror"
                                autocomplete="username"
                            >
                            <i class="fa-regular fa-user icon"></i>
                        </div>

                        {{-- Password --}}
                        <div class="input-field">
                            <input
                                type="password"
                                name="password"
                                class="password"
                                placeholder="Password"
                                autocomplete="current-password"
                            >
                            <i class="fa-solid fa-lock icon"></i>
                            <i class="fa-regular fa-eye-slash showHidePw"></i>
                        </div>

                        {{-- Remember Me / Forgot Password --}}
                        <div class="checkbox-text">
                            <div class="checkbox-content">
                                <input
                                    type="checkbox"
                                    id="logCheck"
                                    name="remember"
                                    value="1"
                                    {{ old('remember') ? 'checked' : '' }}
                                >
                                <label for="logCheck" class="text">
                                    Remember me
                                </label>
                            </div>

                            <a href="{{ route('account.resetpassword') }}" class="text">
                                Forgot Password?
                            </a>
                        </div>

                        {{-- Submit --}}
                        <div class="input-field button">
                            <input type="submit" value="Login Now">
                        </div>
                    </form>

                    <div class="login-signup">
                        <span class="text">
                            Don't Have an Account?
                            <a href="{{ route('account.register') }}" class="text">
                                Sign up now
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection