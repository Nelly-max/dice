{{-- 503: see errors/layout.blade.php --}}
@include('errors.layout', [
    'code' => 503,
    'title' => 'We\'ll be right back',
    'message' => 'Smart Market is being updated. This usually takes a few minutes. Thanks for your patience.',
    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    'actions' => ['reload'],
    'showLinks' => false,
])
