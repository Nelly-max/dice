@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">

                    <span class="title">Enter New Password</span>
                    <p class="subtitle">Choose a new password for your account.</p>

                    @if ($errors->any())
                        <div class="form-error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="form-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('password.reset.update') }}" method="POST" data-notify-loading="Updating your password…">
                        @csrf

                        <div class="input-field">
                            <input
                                type="password"
                                name="password"
                                class="password"
                                placeholder="Enter Password"
                                autocomplete="new-password"
                            >
                            <i class="fa-solid fa-lock icon"></i>
                            <i class="fa-regular fa-eye-slash showHidePw"></i>
                        </div>

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

                        <div class="input-field button">
                            <input type="submit" value="Update Password">
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</main>
@endsection