{{-- Sliding leaflets. JS hooks: .carousel-container, .carousel, .card, and the two <i> arrows (#left / #right) --}}
@if (count($images))
    <section class="carousel-container" aria-label="Leaflets">
        <i id="left" class="fa-solid fa-angle-left" role="button" aria-label="Previous"></i>
        <ul class="carousel">
            @foreach ($images as $image)
                <li class="card">
                    <div class="img">
                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" draggable="false" loading="lazy">
                    </div>
                </li>
            @endforeach
        </ul>
        <i id="right" class="fa-solid fa-angle-right" role="button" aria-label="Next"></i>
    </section>
@endif
