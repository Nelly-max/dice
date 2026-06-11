<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMART MARKET || Home City</title>

    <!-- Vite CSS -->
    @vite([
        'resources/css/modal.css', 
        'resources/css/reused.css', 
        'resources/css/homecity.css', 
        
        'resources/js/reused.js'
    ])

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- Remix icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet"/>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    {{-- Navbar --}}
    @include('HomeCity.partials.nav')

    {{-- Main Content --}}
    <main class="wrapper">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('HomeCity.partials.footer')

</body>
</html>
