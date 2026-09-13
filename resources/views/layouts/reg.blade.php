<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SMART MARKET || Account</title>

    <!-- Vite CSS -->
    @vite([
            'resources/css/reused.css',
            'resources/css/main.css',
            'resources/css/modal.css',
            
            'resources/js/signup.js',
            'resources/js/reused.js',
            'resources/js/modal.js',
            ])

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- ===============Iconscout CSS=========== -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">

    <!-- Remix icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet"/>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    {{-- Main Content --}}
    <div class="centered-forms">
        @yield('content')
    </div>
</body>
</html>
