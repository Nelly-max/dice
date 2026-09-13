@extends('layouts.reg')

@section('content')

    <div>
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                    <span class="title">Log In</span> 

                    <form action="/account/login" method="POST">
                        @csrf
                        <div class="input-field">
                            <!-- login_identifier handles either username/account or email -->
                            <input type="text" name="login_identifier" placeholder="Account No. / Email" required value="{{ old('login_identifier') }}">
                            <i class="uil uil-user"></i>
                        </div>

                        <div class="input-field">
                            <input type="password" name="password" class="password" placeholder="Password" required>
                            <i class="uil uil-lock icon"></i>
                            <i class="uil uil-eye-slash showHidePw"></i>
                        </div>
                        
                        <div class="checkbox-text">
                            <div class="checkbox-content">
                                <input type="checkbox" id="logCheck" name="remember">
                                <label for="logCheck" class="text">Remember me</label>
                            </div>
                            
                            <!-- Update with password reset route -->
                            <a href="{{ route('account.resetpassword') }}" class="text">Forgot Password?</a>
                        </div>

                        <div class="input-field button">
                            <input type="submit" class="btn-submit" value="Log in">
                        </div>                
                    </form>

                    <div class="login-signup">
                        <span class="text">Not a member?
                            <a class="text signup-link">signup now</a>
                        </span>
                    </div>
                </div>

                <!-- ==============Registration Form================ -->
                <div class="main-form signup">

                <!-- ADD THIS ERROR VISIBILITY BLOCK HERE -->
                    @if ($errors->any())
                        <div style="background-color: #fce4e4; border: 1px solid #fcc2c2; color: #cc0000; padding: 10px; margin-top: 10px; border-radius: 4px; font-size: 14px;">
                            <ul style="margin: 0; padding-left: 20px;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <span class="title">Registration</span> 

                    <form action="{{ route('account.register.create') }}" method="POST">
                        @csrf
                        <div class="input-field">
                            <input type="text" name="name" placeholder="Enter Name" required value="{{ old('name') }}">
                            <i class="uil uil-house-user"></i>
                        </div>
                        <div class="input-field">
                            <input type="email" name="email" placeholder="Enter Email" required value="{{ old('email') }}">
                            <i class="uil uil-envelope icon"></i>
                        </div>
                        <div class="input-field">
                            <input type="text" name="phone_number" placeholder="Mobile Number" required value="{{ old('phone_number') }}">
                            <i class="uil uil-phone"></i>
                        </div>
                        
                        <!-- Updated names to 'gender' matching customer_accounts database schema -->
                        <div class="input-radio">
                            <span><input type="radio" name="gender" value="male" required {{ old('gender') === 'male' ? 'checked' : '' }}> Male</span>
                            <span><input type="radio" name="gender" value="female" required {{ old('gender') === 'female' ? 'checked' : '' }}> Female</span>
                        </div>
                        
                        <!-- Fixed password block: type changed to password, class password added -->
                        <div class="input-field">
                            <input type="password" name="password" class="password" placeholder="Password" required>
                            <i class="uil uil-lock icon"></i>
                            <i class="uil uil-eye-slash showHidePw"></i>
                        </div>
                        <div class="input-field">
                            <input type="password" name="password_confirmation" class="password" placeholder="Confirm Password" required>
                            <i class="uil uil-lock icon"></i>
                            <i class="uil uil-eye-slash showHidePw"></i>
                        </div>

                        <div class="input-field button">
                            <input type="submit" value="Create Account" class="btn-submit">
                        </div>    
                    </form>
                    
                    <div class="login-signup">
                        <span class="text">Has an Account?
                            <a class="text login-link">signin now</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
