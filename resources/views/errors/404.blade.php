{{-- 404: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 404,
    'title' => 'We couldn\'t find that page',
    'message' => 'The link may be broken, or the page, product or shop may have moved or been removed.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/><path d="M8.5 11h5"/></svg>',
    'actions' => ['back', 'section', 'home'],
    'showLinks' => true,
])
