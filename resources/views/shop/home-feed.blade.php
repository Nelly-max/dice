{{--
    The lower half of a product shop's home page (Home Market, Liquor Hub, Glam Room, Farm Market).
    Laid out like Carrefour's: full-width sections stacked one under the other, never side by side.
    Product rows alternate with a full-width leaflet carousel or a banner strip, so the page keeps
    a rhythm instead of six product rows in a row and the images all in one place.

      Fresh in store
      This week's offers           (leaflets, Carousel_1)
      category row 1
      banners 1
      category row 2
      More deals                   (leaflets, Carousel_2)
      Hot deals                    (on a tinted band, with its countdown)
      Everyday products
      banners 2
      the other category rows      (a banner strip after every two when there are banners left)
      More offers                  (leaflets, Carousel_3)
      banners 3

    @include('shop.home-feed', ['ns' => 'HomeMarket'])   (uses the home page's $rows, $shop, $leaflets, $banners)
    Styles: .hm-band / .hm-leaflets in homemarket-ui.css.
--}}
@php
    $row = $ns . '.partials.product-row';
    $leafletsPartial = $ns . '.partials.leaflets';
    $bannersPartial = $ns . '.partials.banners';
    $catRows = collect($shop['categoryRows'] ?? []);
@endphp

@include($row, ['title' => 'Fresh in store', 'products' => $rows['fresh']])

@include('shop.leaflet-section', ['partial' => $leafletsPartial, 'title' => "This week's offers", 'images' => $leaflets[1] ?? []])

@if ($first = $catRows->get(0))
    @include($row, ['title' => $first->title, 'products' => $first->products, 'more' => $first->url])
@endif

@include($bannersPartial, ['images' => $banners[1] ?? [], 'class' => 'static_two'])

@if ($second = $catRows->get(1))
    @include($row, ['title' => $second->title, 'products' => $second->products, 'more' => $second->url])
@endif

@include('shop.leaflet-section', ['partial' => $leafletsPartial, 'title' => 'More deals', 'images' => $leaflets[2] ?? []])

@if (($rows['hot'] ?? collect())->isNotEmpty())
    <section class="hm-band hm-band--deals" aria-label="Hot deals">
        @include($row, ['id' => 'hot-deals', 'title' => 'Hot deals', 'products' => $rows['hot'], 'more' => isset($shop) ? $shop['dealsUrl'] : null, 'endsAt' => $shop['endsAt'] ?? null])
    </section>
@endif

@include($row, ['title' => 'Everyday products', 'products' => $rows['everyday']])

@include($bannersPartial, ['images' => $banners[2] ?? [], 'class' => 'static_one'])

@foreach ($catRows->slice(2) as $catRow)
    @include($row, ['title' => $catRow->title, 'products' => $catRow->products, 'more' => $catRow->url])
@endforeach

@include('shop.leaflet-section', ['partial' => $leafletsPartial, 'title' => 'More offers', 'images' => $leaflets[3] ?? []])

@include($bannersPartial, ['images' => $banners[3] ?? [], 'class' => 'static_three'])
