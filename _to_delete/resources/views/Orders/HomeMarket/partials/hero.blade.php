{{-- Full-width auto-sliding hero. JS hooks: .slider .list .item #prev #next .dots li --}}
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
@endif
