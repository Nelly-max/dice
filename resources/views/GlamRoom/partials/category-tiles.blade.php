{{-- Category tiles. Edit the list in config/glamroom.php (category_tiles, image tiles from
     {MEDIA_ROOT}/img/glamRoom/Category_icons). While that list is empty, icon tiles are shown
     that open the search for each category. --}}
@php
    $tiles = config('glamroom.category_tiles') ?: [];
    $iconTiles = [
        ['Skincare', 'fa-solid fa-pump-soap'],
        ['Makeup', 'fa-solid fa-wand-magic-sparkles'],
        ['Hair Care', 'fa-solid fa-scissors'],
        ['Wigs', 'fa-solid fa-feather'],
        ['Fragrance', 'fa-solid fa-spray-can-sparkles'],
        ['Bath & Body', 'fa-solid fa-bath'],
        ['Nails', 'fa-solid fa-hand-sparkles'],
        ['Men', 'fa-solid fa-user'],
    ];
@endphp
<section class="category-icons main-cat-icons hc-tiles" aria-label="Categories">
    @forelse ($tiles as $tile)
        @php
            $tileLabel = mb_strtolower($tile['label'] ?? '');
            $href = match (true) {
                    str_contains($tileLabel, 'hot') => route('glamroom.deals'),
                    str_contains($tileLabel, 'exclusive') => route('glamroom.exclusives'),
                    !empty($tile['category_id']) => route('glamroom.category', $tile['category_id']),
                    !empty($tile['href']) && $tile['href'] !== '#' => $tile['href'],
                    default => route('glamroom.search', ['q' => $tile['label']]),
                };
        @endphp

        <a href="{{ $href }}" class="category-icon">
            <img src="{{ app(\App\Services\GlamRoom\MediaGallery::class)->categoryIcon($tile['file']) }}"
                 alt="" loading="lazy">
            <span>{{ $tile['label'] }}</span>
        </a>
    @empty
        @foreach ($iconTiles as [$label, $icon])
            <a href="{{ route('glamroom.search', ['q' => $label]) }}" class="category-icon">
                <span class="hc-tile-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    @endforelse
</section>
