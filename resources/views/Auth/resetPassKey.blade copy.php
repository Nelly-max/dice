@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">

                    <span class="title">Enter OTP</span>

                    @if (session('success'))
                        <div class="text">{{ session('success') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="text" style="color: red;">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form
                        action="{{ route('otp.verify') }}"
                        method="POST"
                        id="otp-form"
                        data-expires-at="{{ session('password_reset_expires') ? \Carbon\Carbon::parse(session('password_reset_expires'))->timestamp * 1000 : 0 }}"
                        data-resend-url="{{ route('otp.resend') }}"
                    >
                        @csrf

                        <input type="hidden" name="otp" id="otp">

                        <div id="digit-container" class="digit-container">
                            @for ($i = 0; $i < 6; $i++)
                                <input
                                    type="text"
                                    maxlength="1"
                                    class="digit-input"
                                    autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                    required
                                    aria-label="Code character {{ $i + 1 }}"
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
                            <span id="otp-countdown">02:00</span>
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
                            <input type="submit" value="Reset Password">
                        </div>
                    </form>

                    <div class="login-signup"></div>

                </div>
            </div>
        </div>
    </div>
</main>
@endsection