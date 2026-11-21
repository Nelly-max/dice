{{-- 403: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 403,
    'title' => 'You can\'t open this page',
    'message' => 'Your account doesn\'t have access to this page. If you think it should, sign in with the right account or contact us.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/></svg>',
    'actions' => ['login', 'back', 'section', 'home'],
    'showLinks' => false,
])
