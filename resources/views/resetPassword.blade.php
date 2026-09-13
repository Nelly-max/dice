@extends('layouts.reg')

@section('content')

    <div class="centered-forms">
        <div class="centered-forms-container">
            <div class="main-forms">
                <div class="main-form login">
                   <span class="title">Reset your password</span> 
    
                   <form action="#">
                        <div class="input-field">
                            <input type="text" placeholder="Enter your email" required>
                            <i class="uil uil-user"></i>
                        </div>
                        
                        <div class="checkbox-text">                            
                            <!-- <a href="register.html" class="text">Login</a> -->
                        </div>

                        <span class="text">An OTP will be sent to your email that will help your reset your password?
                            <a href="register.html" class="text signup-link">Login now</a>
                        </span>
    
                        <div class="input-field button">
                            <input type="button" value="Reset Password">
                        </div>                
                    </form>
    
                    <div class="login-signup">
                    </div>
                </div>
            </div>
        </div>
    </div>
    
@endsection