{{-- A full-width leaflet carousel with a title like the product rows (shop/home-feed). Nothing when
     the folder is empty. --}}
@if (count($images))
    <section class="hm-leaflets" aria-label="{{ $title }}">
        <div class="hm-sec-head">
            <h3 class="sub-heading">{{ $title }}</h3>
            <small>Swipe for more</small>
        </div>
        @include($partial, ['images' => $images])
    </section>
@endif
