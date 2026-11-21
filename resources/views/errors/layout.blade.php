{{--
    Error pages (403, 404, 419, 429, 500, 503, …). Laravel renders errors/{code}.blade.php,
    else errors/4xx or errors/5xx; each sets $code, $title, $message and $icon and includes this.

    Self-contained on purpose: no Vite build, no database, no shop header. A 500 can come from
    a database or build problem, so this page must not need either. Styles are inline; every
    link goes through $link(), which falls back to a plain URL if a route is missing.

    It follows the section the visitor was in (/home-market/… → Home Market colours and a
    "Back to Home Market" button), and the visitor's light / dark setting.
--}}
@php
    use Illuminate\Support\Facades\Route;

    $link = function (string $name, string $fallback = '/') {
        try { return Route::has($name) ? route($name) : url($fallback); } catch (\Throwable $e) { return $fallback; }
    };

    // section of the site the visitor was in: first path segment => [name, route, fallback, accent]
    $sections = [
        'home-market' => ['Home Market', 'homemarket.home', '/home-market', '#e8590c'],
        'liquor-hub'  => ['Liquor Hub', 'liquorhub.home', '/liquor-hub', '#9f1239'],
        'glam-room'   => ['Glam Room', 'glamroom.home', '/glam-room', '#db2777'],
        'farm-market' => ['Farm Market', 'farmmarket.home', '/farm-market', '#4d7c0f'],
        'gas'         => ['Cooking Gas', 'gas.products.index', '/gas', '#2563eb'],
        'water'       => ['Water', 'water.index', '/water', '#0891b2'],
        'home-city'   => ['Home City', 'homecity.index', '/home-city', '#0f766e'],
        'airbnb'      => ['Airbnb', 'airbnb.index', '/airbnb', '#7c3aed'],
        'hub'         => ['your Hub', 'hub.index', '/hub/home', '#16a34a'],
        'cart'        => ['your cart', 'cart.index', '/cart', '#16a34a'],
    ];
    $segment = strtolower((string) request()->segment(1));
    $section = $sections[$segment] ?? null;
    $accent = $section[3] ?? '#16a34a';

    $code = $code ?? 500;
    $is5xx = $code >= 500;

    // a message given with abort(403, '…') is shown for 4xx; server errors never show internals
    $detail = null;
    if (!$is5xx && isset($exception) && method_exists($exception, 'getMessage')) {
        $m = trim((string) $exception->getMessage());
        $generic = ['', 'Not Found', 'Forbidden', 'Unauthorized', 'Page Expired', 'Too Many Requests', 'Method Not Allowed', 'This action is unauthorized.'];
        $internal = preg_match('/could not be found|No query results|SQLSTATE|App\\\\|\\\\Models\\\\/i', $m);
        if (!in_array($m, $generic, true) && !$internal && mb_strlen($m) <= 200) $detail = $m;
    }

    // signed in? (guarded: never let the error page fail)
    $signedIn = false;
    if (!$is5xx) {
        try { $signedIn = auth('customer')->check(); } catch (\Throwable $e) {}
    }

    $home = $link('home', '/');
    $actions = $actions ?? ['back', 'section', 'home'];

    // one filled (primary) button: the first that applies of try again / sign in / back to section / home
    $primary = collect($actions)->first(fn ($a) => match ($a) {
        'reload' => true,
        'login' => !$signedIn,
        'section' => (bool) $section,
        'home' => true,
        default => false,
    });
    $ref = $is5xx ? strtoupper(substr(md5(microtime(true) . request()->fullUrl()), 0, 8)) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="{{ $accent }}">
    <title>{{ $title }} · Smart Market</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
    <script>
        // same light / dark choice as the rest of the site (partials/theme-init: "mode")
        try {
            var mode = localStorage.getItem('mode');
            if (mode === 'dark' || (mode !== 'light' && window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
        } catch (e) {}
    </script>
    <style>
        :root {
            --accent: {{ $accent }};
            --bg: #f5f7f6;
            --card: #ffffff;
            --ink: #14201b;
            --muted: #5b6b64;
            --line: #e3e9e6;
            --soft: color-mix(in srgb, var(--accent) 10%, #ffffff);
        }
        html.dark {
            --bg: #0f1513;
            --card: #17201d;
            --ink: #eef4f1;
            --muted: #9fb0a8;
            --line: #26332e;
            --soft: color-mix(in srgb, var(--accent) 18%, #17201d);
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            background: var(--bg);
            color: var(--ink);
            font: 600 16px/1.5 "Nunito", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: calc(14px + env(safe-area-inset-top)) 20px 14px;
        }
        .brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; font-weight: 900; font-size: 1.05rem; }
        .brand img { width: 34px; height: 34px; border-radius: 10px; }
        .top a.help { color: var(--muted); font-size: .9rem; font-weight: 800; text-decoration: none; }
        .top a.help:hover { color: var(--ink); }
        main { flex: 1; display: grid; place-items: center; padding: 16px 16px 40px; }
        .card {
            width: 100%;
            max-width: 620px;
            padding: 40px 32px 32px;
            border: 1px solid var(--line);
            border-radius: 28px;
            background: var(--card);
            box-shadow: 0 24px 60px -32px rgba(0, 0, 0, .25);
            text-align: center;
        }
        .art { position: relative; width: 112px; height: 112px; margin: 0 auto 18px; }
        .art .ring { position: absolute; inset: 0; border-radius: 50%; background: var(--soft); }
        .art .ring::after { content: ""; position: absolute; inset: -10px; border: 2px dashed color-mix(in srgb, var(--accent) 35%, transparent); border-radius: 50%; animation: spin 24s linear infinite; }
        .art svg { position: absolute; inset: 0; margin: auto; width: 52px; height: 52px; color: var(--accent); }
        @keyframes spin { to { transform: rotate(360deg); } }
        @media (prefers-reduced-motion: reduce) { .art .ring::after { animation: none; } }
        .code { display: inline-block; margin-bottom: 8px; padding: 3px 12px; border-radius: 999px; background: var(--soft); color: var(--accent); font-size: .8rem; font-weight: 900; letter-spacing: .08em; }
        h1 { margin: 0 0 8px; font-size: clamp(1.45rem, 4.5vw, 1.9rem); font-weight: 900; line-height: 1.2; }
        p.msg { margin: 0 auto; max-width: 42ch; color: var(--muted); font-size: 1rem; }
        p.detail { margin: 14px auto 0; max-width: 46ch; padding: 10px 14px; border-radius: 14px; background: var(--soft); color: var(--ink); font-size: .92rem; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 26px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 18px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--card);
            color: var(--ink);
            font: inherit;
            font-weight: 800;
            font-size: .95rem;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s ease, background .15s ease, border-color .15s ease;
        }
        .btn:hover { border-color: color-mix(in srgb, var(--accent) 45%, var(--line)); transform: translateY(-1px); }
        .btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--accent) 45%, transparent); outline-offset: 2px; }
        .btn.primary { border-color: var(--accent); background: var(--accent); color: #fff; }
        .btn.primary:hover { filter: brightness(1.06); }
        .btn svg { width: 18px; height: 18px; }
        .links { margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--line); }
        .links small { display: block; margin-bottom: 10px; color: var(--muted); font-size: .8rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        .links nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; }
        .links a { padding: 6px 12px; border-radius: 999px; background: var(--bg); color: var(--ink); font-size: .86rem; font-weight: 800; text-decoration: none; }
        .links a:hover { background: var(--soft); color: var(--accent); }
        .ref { margin-top: 18px; color: var(--muted); font-size: .8rem; }
        .ref code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: var(--ink); }
        footer { padding: 0 16px calc(18px + env(safe-area-inset-bottom)); color: var(--muted); font-size: .82rem; text-align: center; }
        @media (max-width: 480px) {
            .card { padding: 32px 20px 24px; border-radius: 22px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <header class="top">
        <a class="brand" href="{{ $home }}"><img src="{{ asset('img/favicon.png') }}" alt="" width="34" height="34"> Smart Market</a>
        <a class="help" href="{{ url('/help') }}">Help</a>
    </header>

    <main>
        <section class="card" role="alert" aria-live="polite">
            <div class="art" aria-hidden="true">
                <span class="ring"></span>
                {!! $icon !!}
            </div>

            <span class="code">ERROR {{ $code }}</span>
            <h1>{{ $title }}</h1>
            <p class="msg">{{ $message }}</p>
            @if ($detail)
                <p class="detail">{{ $detail }}</p>
            @endif

            <div class="actions">
                @foreach ($actions as $a)
                    @switch($a)
                        @case('reload')
                            <button type="button" @class(['btn', 'primary' => $primary === 'reload']) onclick="location.reload()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                                Try again
                            </button>
                            @break
                        @case('login')
                            @unless ($signedIn)
                                <a @class(['btn', 'primary' => $primary === 'login']) href="{{ $link('login', '/account/login') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                                    Sign in
                                </a>
                            @endunless
                            @break
                        @case('back')
                            <button type="button" class="btn" onclick="history.length > 1 ? history.back() : (location.href = '{{ $section ? $link($section[1], $section[2]) : $home }}')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                                Go back
                            </button>
                            @break
                        @case('section')
                            @if ($section)
                                <a @class(['btn', 'primary' => $primary === 'section']) href="{{ $link($section[1], $section[2]) }}">Back to {{ $section[0] }}</a>
                            @endif
                            @break
                        @case('home')
                            <a @class(['btn', 'primary' => $primary === 'home']) href="{{ $home }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/></svg>
                                Smart Market home
                            </a>
                            @break
                    @endswitch
                @endforeach
            </div>

            @if (!empty($showLinks))
                <div class="links">
                    <small>Or shop</small>
                    <nav aria-label="Shops">
                        @foreach (['home-market', 'liquor-hub', 'glam-room', 'farm-market', 'gas', 'water', 'home-city', 'airbnb'] as $key)
                            @php $s = $sections[$key]; @endphp
                            <a href="{{ $link($s[1], $s[2]) }}">{{ $s[0] }}</a>
                        @endforeach
                    </nav>
                </div>
            @endif

            @if ($ref)
                <p class="ref">If this keeps happening, contact us and quote <code>{{ $ref }}</code> · {{ now()->format('d M Y, H:i') }}</p>
            @endif
        </section>
    </main>

    <footer>&copy; {{ date('Y') }} Smart Market Kenya</footer>
</body>
</html>
