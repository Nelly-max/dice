
@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                    <span class="title">Enter OTP</span>
                    <p class="subtitle">Check your email for the 6-character code.</p>

                    @if(session('success'))
                        <div class="form-success">
                            {{ session('success') }}
                        </div>
                    @endif


                    @error('otp')
                        <div class="form-error" role="alert">
                            {{ $message }}
                        </div>
                    @enderror

                    <form data-notify-loading="Checking your code…"
                        action="{{ route('otp.verify') }}"
                        method="POST"
                        id="otp-form"
                        data-expires-at="{{ session('password_reset_expires') ? \Carbon\Carbon::parse(session('password_reset_expires'))->timestamp * 1000 : 0 }}"
                        data-resend-url="{{ route('otp.resend') }}"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="otp"
                            id="otp"
                            value="{{ old('otp') }}"
                        >

                        <div id="digit-container" class="digit-container">
                            @for ($i = 0; $i < 6; $i++)
                                <input
                                    type="text"
                                    maxlength="1"
                                    class="digit-input"
                                    inputmode="text"
                                    pattern="[A-HJ-NP-Z2-9]"
                                    autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                    aria-label="Verification code character {{ $i + 1 }}"
                                    @if($i > 0) disabled @endif
                                >
                            @endfor
                        </div>

                        <div class="checkbox-text">
                            <span class="text">
                                Enter the 6-character verification code sent to your email.
                            </span>
                        </div>

                        <div class="checkbox-text">
                            <span class="text">Did not receive the code?</span>

                            <span
                                class="text"
                                id="otp-countdown"
                                style="display: inline;"
                            >02:00</span>

                            <a
                                href="{{ route('otp.resend') }}"
                                id="resend-otp"
                                class="text"
                                style="display: none;"
                            >
                                Request New Code
                            </a>
                        </div>

                        <div class="input-field button">
                            <input type="submit" value="Verify OTP">
                        </div>
                    </form>

                    <div class="login-signup">
                        <span class="text">
                            Remembered your password?
                            <a href="{{ route('login') }}" class="text">
                                Sign in
                            </a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
