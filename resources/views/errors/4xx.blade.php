{{-- Any other 4xx status: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 400,
    'title' => 'Something isn\'t right',
    'message' => 'We couldn\'t open this page. Go back and try again.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>',
    'actions' => ['back', 'section', 'home'],
    'showLinks' => false,
])
