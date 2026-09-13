<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SMART MARKET ||</title>

    <!-- Vite CSS -->
    @vite([
            'resources/css/reused.css',
            'resources/css/main.css',
            'resources/css/hub.css',
            
            'resources/js/reused.js',
            'resources/js/toast.js',
            'resources/js/deliveryMap.js',
            ])

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('img/favicon.png') }}">

    <!-- Remix icon CDN Link -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.6.0/fonts/remixicon.css" rel="stylesheet">

    <!-- Box-icons CDN Link -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- font awesome link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <main>
        @yield('content')
    </main>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places,geometry&v=weekly&callback=initDeliveryDirections"
        async
        defer>
    </script>
</body>
</html>
