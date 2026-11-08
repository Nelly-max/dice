<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Homes & properties') · Home City</title>

    {{-- Same page structure as Home Market: homemarket-ui.css styles it and tokens.css gives
         Home City its teal (body[data-subdivision="home-city"]). services-ui.css adds the listing
         page pieces (photos, units, lister). Order matters: shared files first. --}}
    @vite([
        'resources/css/reused.css',
        'resources/css/modal.css',
        'resources/css/homemrkt.css',
        'resources/css/tokens.css',
        'resources/css/homemarket-ui.css',
        'resources/css/components.css',
        'resources/css/responsive.css',
        'resources/css/services-ui.css',
        'resources/css/quick-switch.css',
        'resources/js/softnav.js',
        'resources/js/reused.js',
        'resources/js/modal.js',
        'resources/js/map.js',
        'resources/js/services-ui.js',
        'resources/js/hmsearch.js',
        'resources/js/quick-switch.js',
    ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- Remix icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet"/>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body data-subdivision="home-city" class="@yield('body_class')">
    @include('partials.theme-init')

    @include('HomeCity.partials.nav')

    @yield('content')

    @include('HomeCity.partials.footer')
    {{-- Floating quick switch: other subdivisions, cart, account, settings --}}
    @include('partials.quick-switch')

    {{-- location sheet: "homes within 500 m … 5 km of me" (same sheet as the shops) --}}
    @include('modals.map')

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places"
        async
        defer>
    </script>

</body>
</html>
