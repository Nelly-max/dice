<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Smart Market || Hub</title>

    <!-- Vite CSS -->
    @vite([
            'resources/css/modal.css',
            'resources/css/toast.css',
            'resources/css/reused.css',
            'resources/css/hub.css',
            'resources/css/calendar.css',
            'resources/css/responsive.css',
            'resources/css/hub-ui.css',
            
            'resources/js/reused.js',
            'resources/js/hubnav.js',
            'resources/js/hub.js',
            'resources/js/modal.js',
            'resources/js/select.js',
            'resources/js/riderLocation.js',
            'resources/js/calendar.js',
            ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- ===============Iconscout CSS=========== -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">

    <!-- Box icons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- Remix icons -->

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="hub-body">
    @include('partials.theme-init')

    <div id="all_contents" class="hub-app">
        {{-- Sidebar (+ scrim for the phone drawer) --}}
        @include('partials.Hub.sidebar')

        <main class="hub-container">
            {{-- Top bar --}}
            @include('partials.Hub.topbar')

            <div class="hub-page">
                @yield('content')
            </div>

            {{-- Footer --}}
            @include('partials.Hub.footer')
        </main>

        @include('toast.toast')
        @include('toast.openDeliveryMap')
    </div>
</body>
</html>
