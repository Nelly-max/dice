{{-- Category tiles. Edit the list in config/liquorhub.php (category_tiles, image tiles from
     {MEDIA_ROOT}/img/liquorHub/Category_icons). While that list is empty, icon tiles are shown
     that open the search for each drink. --}}
@php
    $tiles = config('liquorhub.category_tiles') ?: [];
    $iconTiles = [
        ['Whisky', 'fa-solid fa-whiskey-glass'],
        ['Vodka', 'fa-solid fa-bottle-droplet'],
        ['Gin', 'fa-solid fa-martini-glass-citrus'],
        ['Tequila', 'fa-solid fa-lemon'],
        ['Wine', 'fa-solid fa-wine-glass'],
        ['Champagne', 'fa-solid fa-champagne-glasses'],
        ['Beer', 'fa-solid fa-beer-mug-empty'],
        ['Mixers', 'fa-solid fa-glass-water'],
    ];
@endphp
<section class="category-icons main-cat-icons hc-tiles" aria-label="Categories">
    @forelse ($tiles as $tile)
        @php
            $tileLabel = mb_strtolower($tile['label'] ?? '');
            $href = match (true) {
                    str_contains($tileLabel, 'hot') => route('liquorhub.deals'),
                    str_contains($tileLabel, 'exclusive') => route('liquorhub.exclusives'),
                    !empty($tile['category_id']) => route('liquorhub.category', $tile['category_id']),
                    !empty($tile['href']) && $tile['href'] !== '#' => $tile['href'],
                    default => route('liquorhub.search', ['q' => $tile['label']]),
                };
        @endphp

        <a href="{{ $href }}" class="category-icon">
            <img src="{{ app(\App\Services\LiquorHub\MediaGallery::class)->categoryIcon($tile['file']) }}"
                 alt="" loading="lazy">
            <span>{{ $tile['label'] }}</span>
        </a>
    @empty
        @foreach ($iconTiles as [$label, $icon])
            <a href="{{ route('liquorhub.search', ['q' => $label]) }}" class="category-icon">
                <span class="hc-tile-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    @endforelse
</section>
