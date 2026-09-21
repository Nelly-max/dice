<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SMART MARKET || Hub</title>

    <!-- Vite CSS -->
    @vite([
            'resources/css/modal.css',
            'resources/css/toast.css',
            'resources/css/reused.css',
            'resources/css/hub.css',
            'resources/css/calendar.css',
            
            'resources/js/reused.js',
            'resources/js/hub.js',
            'resources/js/modal.js',
            'resources/js/select.js',
            'resources/js/riderLocation.js',
            'resources/js/calendar.js',
            ])

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- ===============Iconscout CSS=========== -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">

    <!-- Box icons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- Remix icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet"/>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

    {{-- Navbar --}}
    @include('partials.Hub.hubNav')

    {{-- Contents --}}
    <div  id="all_contents">
        {{-- Sidebar --}}
        @include('partials.Hub.sidebar')
        <main  class="hub-container">
            {{-- Topbar --}}
            @include('partials.Hub.topbar')

                @yield('content')

            {{-- Footer --}}
            @include('partials.Hub.footer')
        </main>
        @include('toast.toast')
        @include('toast.openDeliveryMap')
    </div>
</body>
</html>
