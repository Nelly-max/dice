{{-- Full-width auto-sliding hero. JS hooks: .slider .list .item #prev #next .dots li
     Images come from {MEDIA_ROOT}/img/liquorHub/Sliders; until that folder has images a
     Liquor Hub banner with bottle photos (public/img/cheersCentral) is shown instead. --}}
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
    <section class="lh-hero" aria-label="Liquor Hub">
        <div class="lh-hero-text">
            <span class="lh-hero-chip"><i class="fa-solid fa-motorcycle" aria-hidden="true"></i> Delivered from shops near you</span>
            <h1>Drinks for every occasion</h1>
            <p>Whisky, gin, wine, champagne, beer and mixers from liquor stores within {{ config('liquorhub.max_radius_m') / 1000 }} km. Pay with M-Pesa.</p>
            <a href="#hot-deals" class="lh-hero-btn">Shop deals <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <small class="lh-hero-age"><i class="fa-solid fa-id-card" aria-hidden="true"></i> 18+ only · drink responsibly</small>
        </div>
        <div class="lh-hero-bottles" aria-hidden="true">
            <img src="{{ asset('img/cheersCentral/gordons.png') }}" alt="" loading="lazy">
            <img src="{{ asset('img/cheersCentral/donjulio.png') }}" alt="">
            <img src="{{ asset('img/cheersCentral/lanson.png') }}" alt="" loading="lazy">
        </div>
    </section>
@endif
