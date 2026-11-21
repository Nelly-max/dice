{{-- 419: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 419,
    'title' => 'This page has expired',
    'message' => 'For your security, pages time out after a while. Reload the page and try again. What you typed may need to be filled in again.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2"/><path d="M9 2h6"/></svg>',
    'actions' => ['reload', 'back', 'home'],
    'showLinks' => false,
])
