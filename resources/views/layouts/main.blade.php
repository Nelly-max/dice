<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Smart Market')</title>

    {{-- link previews (WhatsApp, Facebook, X...) – pages can override title/description --}}
    <meta name="description" content="@yield('description', config('smartmarket.tagline'))">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="Smart Market">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Smart Market')">
    <meta property="og:description" content="@yield('description', config('smartmarket.tagline'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/favicon.png') }}">
    <meta name="twitter:card" content="summary">

    {{-- Smart Market main site (home, settings, information pages). Shares tokens.css with the
         shops, so data-subdivision="smart-market" gives it the Smart Market amber. --}}
    @vite([
        'resources/css/modal.css',
        'resources/css/reused.css',
        'resources/css/tokens.css',
        'resources/css/smart-home.css',

        'resources/js/reused.js',
        'resources/js/modal.js',
        'resources/js/smart-home.js',
    ])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('img/favicon.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="sm-home" data-subdivision="smart-market">
    @include('partials.theme-init')

    @include('partials.main.header')

    <main class="sm-main">
        @yield('content')
    </main>

    @include('partials.main.bottom-nav')
</body>
</html>
