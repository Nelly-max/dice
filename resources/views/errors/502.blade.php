{{-- 502: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 502,
    'title' => 'We\'re having trouble connecting',
    'message' => 'One of our services didn\'t respond. Please try again in a moment.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.6a10 10 0 0 1 14 0"/><path d="M8.5 16.1a5 5 0 0 1 7 0"/><path d="M12 20h.01"/><path d="m2 2 20 20"/></svg>',
    'actions' => ['reload', 'home'],
    'showLinks' => false,
])
