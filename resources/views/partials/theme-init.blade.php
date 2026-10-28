{{-- Applies dark mode before the page paints: the choice saved in Settings ("mode"),
     otherwise the device's light/dark setting. Kept in sync by resources/js/reused.js. --}}
<script>
    (function () {
        try {
            var mode = localStorage.getItem('mode');
            var dark = mode === 'dark' || (mode !== 'light' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (dark) document.body.classList.add('dark-mode');
        } catch (e) {}
    })();
</script>
@include('partials.notify-flash')
