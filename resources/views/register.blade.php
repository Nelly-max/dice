@extends('layouts.reg')

@section('content')
<main class="wrapper">
    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                    <span class="title">Log In</span>


                    <form action="{{ route('business.login') }}" method="POST">
                        @csrf

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

                        <div class="input-field">
                            <input
                                type="text"
                                name="login_email"
                                placeholder="Enter UserId"
                                value="{{ old('login_email') }}"
                                class="@error('login_email') is-invalid @enderror"
                                required
                            >
                            <i class="uil uil-user"></i>
                        </div>

                        <div class="input-field">
                            <input
                                type="password"
                                name="password"
                                class="password"
                                placeholder="Password"
                                required
                            >
                            <i class="uil uil-lock icon"></i>
                            <i class="uil uil-eye-slash showHidePw"></i>
                        </div>

                        <div class="checkbox-text">
                            <div class="checkbox-content">
                                <input type="checkbox" id="logCheck" name="remember">
                                <label for="logCheck" class="text">Remember me</label>
                            </div>

                            <a href="{{ route('reset') }}" class="text">Forgot Password?</a>
                        </div>

                        <div class="input-field button">
                            <input type="submit" value="Login Now">
                        </div>
                    </form>



                    <div class="login-signup">
                        <span class="text">Dont Have a Business Account?
                            <a href="{{ route('account.register') }}" class="text">signup now</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
