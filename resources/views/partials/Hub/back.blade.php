{{-- Back button for Hub pages: goes to the previous page, or to $fallback when there is no history.
     Pass class => 'shortcut' to match the Edit button style in a .shortcuts row. --}}
<a href="{{ $fallback ?? route('hub.account.index') }}" class="{{ $class ?? 'hub-back' }}"
   onclick="if (history.length > 1) { history.back(); return false; }">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ $label ?? 'Back' }}
</a>
