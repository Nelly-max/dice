<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Smart Market || Account</title>

    <!-- Vite CSS -->
    @vite([
            'resources/css/reused.css',
            'resources/css/modal.css',
            'resources/css/main.css',
            'resources/css/responsive.css',
            'resources/css/tokens.css',
            'resources/css/auth.css',

            
            'resources/js/signup.js',
            'resources/js/reused.js',
            'resources/js/modal.js',
            ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- ===============Iconscout CSS=========== -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">

    <!-- Remix icons -->

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="auth-page" data-subdivision="home-market">
    @include('partials.theme-init')

    <div class="auth-shell">
        {{-- Form side --}}
        <div class="auth-panel">
            <div class="centered-forms">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
