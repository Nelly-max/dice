{{-- Full-width auto-sliding hero. JS hooks: .slider .list .item #prev #next .dots li
     Images come from {MEDIA_ROOT}/img/glamRoom/Sliders; until that folder has images a
     pink Glam Room banner is shown instead. --}}
@if (count($slides))
    <div class="slider" role="region" aria-roledescription="carousel" aria-label="Offers">
        <div class="list">
            @foreach ($slides as $slide)
                <div class="item">
                    <img src="{{ $slide['url'] }}" alt="{{ $slide['alt'] }}" @unless ($loop->first) loading="lazy" @endunless>
                </div>
            @endforeach
        </div>

        @if (count($slides) > 1)
            <div class="buttons">
                <button type="button" id="prev" aria-label="Previous slide"><i class="fa-solid fa-angle-left"></i></button>
                <button type="button" id="next" aria-label="Next slide"><i class="fa-solid fa-angle-right"></i></button>
            </div>

            <ul class="dots">
                @foreach ($slides as $slide)
                    <li @class(['active' => $loop->first])></li>
                @endforeach
            </ul>
        @endif
    </div>
@else
    <section class="lh-hero gr-hero" aria-label="Glam Room">
        <div class="lh-hero-text">
            <span class="lh-hero-chip"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i> Delivered from shops near you</span>
            <h1>Beauty, delivered</h1>
            <p>Skincare, makeup, hair care, fragrance and more from beauty shops within {{ config('glamroom.max_radius_m', 3000) / 1000 }} km. Pay with M-Pesa.</p>
            <a href="#hot-deals" class="lh-hero-btn">Shop deals <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="gr-hero-art" aria-hidden="true">
            <span><i class="fa-solid fa-wand-magic-sparkles"></i></span>
            <span><i class="fa-solid fa-spray-can-sparkles"></i></span>
            <span><i class="fa-solid fa-pump-soap"></i></span>
            <span><i class="fa-solid fa-gem"></i></span>
        </div>
    </section>
@endif
