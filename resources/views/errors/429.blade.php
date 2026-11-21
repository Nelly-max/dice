{{-- 429: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 429,
    'title' => 'Too many tries',
    'message' => 'You\'ve made a lot of requests in a short time. Wait a minute, then try again.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14"/><path d="M5 2h14"/><path d="M17 22v-4.17a2 2 0 0 0-.59-1.42L12 12l-4.41 4.41A2 2 0 0 0 7 17.83V22"/><path d="M7 2v4.17a2 2 0 0 0 .59 1.42L12 12l4.41-4.41A2 2 0 0 0 17 6.17V2"/></svg>',
    'actions' => ['reload', 'back', 'home'],
    'showLinks' => false,
])
