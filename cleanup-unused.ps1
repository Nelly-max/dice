# Smart Market: move files nothing uses any more into _to_delete\ (same folder layout).
# Run once from the project folder:   powershell -ExecutionPolicy Bypass -File .\cleanup-unused.ps1
# Then run the app (npm run build, php artisan optimize:clear, click around). If all is well,
# delete the _to_delete folder. If something broke, move that file back from _to_delete.
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

$paths = @(
    # --- old " copy" backups ---
    'resources/css/modal copy.css',
    'resources/js/cart copy.js',
    'resources/js/deliveryMap copy.js',
    'resources/js/map copy.js',
    'resources/js/reused copy.js',
    'resources/js/reused copy 2.js',
    'resources/js/reused copy 3.js',
    'resources/js/riderLocation copy.js',
    'resources/js/riderLocation copy 2.js',
    'resources/views/home.blade copy.php',
    'resources/views/CookingGas/home.blade copy.php',
    'resources/views/Hub/joinTeam.blade copy.php',
    'resources/views/Hub/marketer.blade copy.php',
    'resources/views/Orders/orderplaced.blade copy.php',
    'resources/views/HomeCity/Properties/property-card.blade copy.php',
    'app/Models/Cart copy.php',
    'app/Http/Controllers/Api/PropertyController copy.php',
    'app/Http/Controllers/Web/CookingGas/ProductController copy.php',
    'app/Http/Controllers/Web/HomeCity/PropertyController copy.php',
    'app/Http/Controllers/Web/Payment/PaymentController copy.php',
    'routes/web copy.php',

    # --- views nothing renders or includes ---
    'resources/views/welcome.blade.php',
    'resources/views/layouts/toast.blade.php',
    'resources/views/layouts/modal.blade.php',
    'resources/views/modals/billings.blade.php',
    'resources/views/modals/hub.blade.php',
    'resources/views/modals/modals.blade.php',
    'resources/views/modals/modals.html',
    'resources/views/modals/HomeMarket',          # old copy of the Home Market views
    'resources/views/Orders/HomeMarket',          # another old copy of the Home Market views
    'resources/views/partials/nav.blade.php',
    'resources/views/partials/Hub/hubNav.blade.php',
    'resources/views/shop/category-tiles.blade.php',
    'resources/views/shop/feature-row.blade.php',
    'resources/views/CookingGas/order',
    'resources/views/HomeMarket/partials/info-bar.blade.php',
    'resources/views/LiquorHub/partials/info-bar.blade.php',
    'resources/views/GlamRoom/partials/info-bar.blade.php',
    'resources/views/FarmMarket/partials/info-bar.blade.php',
    'resources/views/HomeMarket/partials/search.blade.php',
    'resources/views/LiquorHub/partials/search.blade.php',
    'resources/views/GlamRoom/partials/search.blade.php',
    'resources/views/FarmMarket/partials/search.blade.php',
    'resources/views/toast/toast.html',
    'resources/views/HomeCity/temp.md',

    # --- scripts and styles nothing loads (homecity.css / LargeCards.css are already out of vite.config.js) ---
    'resources/js/auth.js',
    'resources/js/thumbnails.js',
    'resources/js/v.md',
    'resources/css/homecity.css',
    'resources/css/LargeCards.css',

    # --- PHP that is never used ---
    'app/Http/Controllers/properties.php',        # sample array, not a class
    'app/Http/Middleware/VerifyCsrfToken.php',    # Laravel 12 uses bootstrap/app.php (validateCsrfTokens) instead

    # --- notes and caches ---
    'app/Http/Controllers/Web/cartdata.md',
    '.phpunit.result.cache'
)

$moved = 0; $missing = @()
foreach ($p in $paths) {
    $src = Join-Path $PSScriptRoot $p
    if (-not (Test-Path -LiteralPath $src)) { $missing += $p; continue }
    $dest = Join-Path (Join-Path $PSScriptRoot '_to_delete') $p
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dest) | Out-Null
    Move-Item -LiteralPath $src -Destination $dest
    $moved++
    Write-Host "moved  $p"
}

# folders left empty by the moves
foreach ($d in @('resources/views/modals/HomeMarket', 'resources/views/Orders/HomeMarket', 'resources/views/CookingGas/order')) {
    $full = Join-Path $PSScriptRoot $d
    if ((Test-Path -LiteralPath $full) -and -not (Get-ChildItem -LiteralPath $full -Recurse -Force)) { Remove-Item -LiteralPath $full -Force }
}

Write-Host ""
Write-Host "$moved moved to _to_delete\"
if ($missing.Count) { Write-Host "already gone: $($missing -join ', ')" }
Write-Host "Next: npm run build; php artisan optimize:clear; php artisan view:clear; check the site; then delete _to_delete\"
