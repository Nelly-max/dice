{{-- 413: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 413,
    'title' => 'That\'s too large to send',
    'message' => 'What you tried to upload is bigger than we accept. Use smaller files (under 5 MB each) and try again.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="m6 10 6-6 6 6"/><path d="M4 20h16"/></svg>',
    'actions' => ['back', 'home'],
    'showLinks' => false,
])
