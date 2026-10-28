{{-- Session messages -> toasts (resources/js/notify.js). Included from partials/theme-init. --}}
@php
    $toasts = [];
    foreach (['success' => 'success', 'status' => 'info', 'info' => 'info', 'warning' => 'warning',
              'error' => 'error', 'login_error' => 'error', 'register_error' => 'error'] as $key => $type) {
        $msg = session($key);
        if (is_string($msg) && trim($msg) !== '') {
            $toasts[] = ['type' => $type, 'message' => $msg];
        }
    }
    $bag = $errors ?? null;
    if ($bag && $bag->any() && ! collect($toasts)->contains('type', 'error')) {
        $toasts[] = $bag->count() > 1
            ? ['type' => 'error', 'title' => 'Please check the form', 'message' => 'Fix the ' . $bag->count() . ' highlighted fields and try again.']
            : ['type' => 'error', 'title' => 'Please check the form', 'message' => $bag->first()];
    }
@endphp
@if ($toasts)
    <div hidden data-notify-flash='@json($toasts)'></div>
@endif
