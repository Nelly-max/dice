{{-- Category tiles. Edit the list in config/farmmarket.php (category_tiles, image tiles from
     {MEDIA_ROOT}/img/farmMarket/Category_icons). While that list is empty, icon tiles are shown
     that open the search for each category (Organic opens ?organic=1). --}}
@php
    $tiles = config('farmmarket.category_tiles') ?: [];
    $iconTiles = [
        ['Vegetables', 'fa-solid fa-carrot', null],
        ['Fruits', 'fa-solid fa-apple-whole', null],
        ['Cereals', 'fa-solid fa-wheat-awn', null],
        ['Legumes', 'fa-solid fa-seedling', null],
        ['Dairy & Eggs', 'fa-solid fa-egg', null],
        ['Meat & Fish', 'fa-solid fa-fish', null],
        ['Herbs & Spices', 'fa-solid fa-leaf', null],
        ['Organic', 'fa-solid fa-spa', route('farmmarket.home', ['organic' => 1])],
    ];
@endphp
<section class="category-icons main-cat-icons hc-tiles" aria-label="Categories">
    @forelse ($tiles as $tile)
        @php
            $tileLabel = mb_strtolower($tile['label'] ?? '');
            $href = match (true) {
                    str_contains($tileLabel, 'hot') => route('farmmarket.deals'),
                    str_contains($tileLabel, 'exclusive') => route('farmmarket.exclusives'),
                    !empty($tile['category_id']) => route('farmmarket.category', $tile['category_id']),
                    !empty($tile['href']) && $tile['href'] !== '#' => $tile['href'],
                    default => route('farmmarket.search', ['q' => $tile['label']]),
                };
        @endphp
        <a href="{{ $href }}" class="category-icon">
            <img src="{{ app(\App\Services\FarmMarket\MediaGallery::class)->categoryIcon($tile['file']) }}"
                 alt="" loading="lazy">
            <span>{{ $tile['label'] }}</span>
        </a>
    @empty
        @foreach ($iconTiles as [$label, $icon, $href])
            <a href="{{ $href ?? route('farmmarket.search', ['q' => $label]) }}" class="category-icon">
                <span class="hc-tile-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    @endforelse
</section>
