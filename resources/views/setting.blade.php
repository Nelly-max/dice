@extends('layouts.app')

@section('content')

<main  class="wrapper">
    <div class="contents all-settings">
        <section class="cat-settings">
            <h4>General</h4>
            <div class="settings">
                <a href="#" class="setting">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <h5>About</h5>
                </a>
                <a href="#" class="setting">
                    <i class="fa-solid fa-link"></i>
                    <h5>Share Link</h5>
                </a>
                <a href="#" class="setting">
                    <i class="fa-solid fa-phone-volume"></i>
                    <h5>Support</h5>
                </a>
                <a href="#" class="setting">
                    <i class="fa-solid fa-book-open-reader"></i>
                    <h5>FAQ</h5>
                </a>
                <!-- <a href="#" class="setting">
                    <i class="fa-solid fa-bell"></i>
                    <h5>Notifications</h5>
                </a> -->
                <a href="#" class="setting">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <h5>Terms & Conditions</h5>
                </a>
                <div class="setting theme">
                    <div id="toggle" class="toggle theme-btn">
                        <i class="fa-solid fa-moon dark"></i>
                        <i class="fa-regular fa-sun light"></i>
                    </div>
                    <h5 class="theme-btn" id="text">Dark Mode</h5>
                </div>
            </div>
        </section>
        
        <section class="cat-settings">
            <h4>socials</h4>
            <div class="settings">
                <a href="#" class="setting">
                    <i class="fa-brands fa-youtube"></i>
                    <h5>YouTube</h5>
                </a>
                <a href="#" class="setting">
                    <i class="fa-brands fa-instagram"></i>
                    <h5>Instagram</h5>
                </a>
                <a href="#" class="setting">
                    <i class="fa-brands fa-tiktok"></i>
                    <h5>Tiktok</h5>
                </a>
                <a href="#" class="setting">
                    <i class="fa-brands fa-x-twitter"></i>
                    <h5>X</h5>
                </a>                        
                <a href="#" class="setting">
                    <i class="fa-brands fa-discord"></i>
                    <h5>Discord</h5>
                </a>
            </div>
        </section>
    </div>
</main>  

@endsection