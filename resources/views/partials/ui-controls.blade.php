{{-- On every page: the custom select (with search) and date picker (resources/js/ui-controls.js)
     and skeleton loading between pages and for pictures (resources/js/skeleton.js).
     Included from partials/theme-init (all shop, Hub and checkout layouts) and layouts/app + reg. --}}
@vite(['resources/css/ui-controls.css', 'resources/css/skeleton.css', 'resources/js/ui-controls.js', 'resources/js/skeleton.js'])
