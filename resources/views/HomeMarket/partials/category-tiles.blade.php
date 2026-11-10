{{-- Category image tiles. Edit the list in config/homemarket.php (category_tiles). --}}
<section class="category-icons main-cat-icons" aria-label="Categories">
    @foreach (config('homemarket.category_tiles') as $tile)
        @php
            $tileLabel = mb_strtolower($tile['label'] ?? '');
            $href = match (true) {
                    str_contains($tileLabel, 'hot') => route('homemarket.deals'),
                    str_contains($tileLabel, 'exclusive') => route('homemarket.exclusives'),
                    !empty($tile['category_id']) => route('homemarket.category', $tile['category_id']),
                    !empty($tile['href']) && $tile['href'] !== '#' => $tile['href'],
                    default => route('homemarket.search', ['q' => $tile['label']]),
                };
        @endphp

        <a href="{{ $href }}" class="category-icon">
            <img src="{{ app(\App\Services\HomeMarket\MediaGallery::class)->categoryIcon($tile['file']) }}"
                 alt="" loading="lazy">
            <span>{{ $tile['label'] }}</span>
        </a>
    @endforeach
</section>
