{{-- 405: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 405,
    'title' => 'That didn\'t work',
    'message' => 'This page can\'t be opened that way. Go back and try again from the page you were on.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
    'actions' => ['back', 'section', 'home'],
    'showLinks' => false,
])
