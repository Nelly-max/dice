<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Smart Market || toast</title>

    <!-- Vite CSS -->
    @vite([
            'resources/css/reused.css',
            'resources/css/main.css',
            'resources/css/toast.css',
            
            'resources/js/reused.js',
            'resources/js/toast.js',
            'resources/js/cart.js',
            ])

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- Remix icon CDN Link -->

    <!-- Box-icons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- font awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    @include('partials.theme-init')
    <main>
        @yield('taost')
    </main>

</body>
</html>
