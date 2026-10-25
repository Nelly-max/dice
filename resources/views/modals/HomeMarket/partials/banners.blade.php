@if (count($images))
    <section class="static-banners {{ $class }}">
        @foreach ($images as $image)
            <div class="static-banner">
                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy">
            </div>
        @endforeach
    </section>
@endif
