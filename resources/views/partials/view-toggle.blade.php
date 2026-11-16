{{-- Grid / list switch on the Home City and Airbnb results pages. The choice is remembered
     (App\Support\ListingFilters::rememberView). Expects $view: grid | list. --}}
<div class="lf-view" role="group" aria-label="Layout">
    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" @class(['lf-view-btn', 'is-active' => $view === 'grid']) aria-label="Show as cards" @if ($view === 'grid') aria-current="true" @endif>
        <i class="fa-solid fa-table-cells-large" aria-hidden="true"></i>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" @class(['lf-view-btn', 'is-active' => $view === 'list']) aria-label="Show as a list" @if ($view === 'list') aria-current="true" @endif>
        <i class="fa-solid fa-list" aria-hidden="true"></i>
    </a>
</div>
