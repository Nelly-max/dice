{{-- Category image tiles. Edit the list in config/homemarket.php (category_tiles). --}}
<section class="category-icons main-cat-icons" aria-label="Categories">
    @foreach (config('homemarket.category_tiles') as $tile)
        @php
            $href = $tile['href']
                ?? (!empty($tile['category_id'])
                    ? route('homemarket.home', ['category' => $tile['category_id']])
                    : '#');
        @endphp

        <a href="{{ $href }}" class="category-icon">
            <img src="{{ app(\App\Services\HomeMarket\MediaGallery::class)->categoryIcon($tile['file']) }}"
                 alt="" loading="lazy">
            <span>{{ $tile['label'] }}</span>
        </a>
    @endforeach
</section>
