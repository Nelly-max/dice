{{--
    Shop by brand, skin type, concern, hair type ... Expects $shopBy, $brands, $activeFilter (ItemsController@index).
    The home page shows one "Shop by" button (plus the active filter, if any); the groups open in a
    popup (<dialog>; a bottom sheet on phones), each group a sideways slider with arrows.
    Styles: resources/css/services-ui.css (.gr-shopby*). Inline handlers only, so it survives soft navigation.
--}}
@php
    $shopBy = $shopBy ?? collect();
    $brands = $brands ?? collect();
    $current = request('shop_by');
    $currentBrand = request('brand');
    $groupCount = $shopBy->count() + ($brands->isNotEmpty() ? 1 : 0);
@endphp

@if ($groupCount || !empty($activeFilter))
<section class="gr-shopby" aria-label="Shop by">

    <div class="gr-shopby-bar">
        @if ($groupCount)
            <button type="button" class="gr-shopby-btn" aria-haspopup="dialog" aria-controls="grShopBy"
                    onclick="document.getElementById('grShopBy').showModal()">
                <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                <span>Shop by</span>
                <small>Skin, hair, concern{{ $brands->isNotEmpty() ? ', brand' : '' }}…</small>
            </button>
        @endif

        @if (!empty($activeFilter))
            <span class="gr-shopby-active">
                <strong>{{ $activeFilter }}</strong>
                <a href="{{ route('glamroom.home') }}" aria-label="Clear filter"><i class="fa-solid fa-xmark" aria-hidden="true"></i></a>
            </span>
        @endif
    </div>

    @if ($groupCount)
        <dialog class="gr-shopby-sheet" id="grShopBy" aria-label="Shop by"
                onclick="if (event.target === this) this.close()">
            <div class="gr-shopby-head">
                <strong><i class="fa-solid fa-sliders" aria-hidden="true"></i> Shop by</strong>
                <button type="button" class="gr-shopby-x" aria-label="Close" onclick="this.closest('dialog').close()">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <div class="gr-shopby-body">
                @if ($brands->isNotEmpty())
                    <div class="gr-shopby-group">
                        <h3>Brand</h3>
                        <div class="gr-shopby-slider">
                            <button type="button" class="gr-shopby-arrow" aria-label="Scroll left"
                                    onclick="this.parentElement.querySelector('.gr-shopby-chips').scrollBy({ left: -240, behavior: 'smooth' })"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                            <div class="gr-shopby-chips">
                                @foreach ($brands as $brand)
                                    <a href="{{ route('glamroom.home', ['brand' => $brand]) }}"
                                       @class(['gr-chip', 'is-active' => $currentBrand === $brand])>{{ $brand }}</a>
                                @endforeach
                            </div>
                            <button type="button" class="gr-shopby-arrow" aria-label="Scroll right"
                                    onclick="this.parentElement.querySelector('.gr-shopby-chips').scrollBy({ left: 240, behavior: 'smooth' })"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                        </div>
                    </div>
                @endif

                @foreach ($shopBy as $group)
                    <div class="gr-shopby-group">
                        <h3>{{ $group->name }}</h3>
                        <div class="gr-shopby-slider">
                            <button type="button" class="gr-shopby-arrow" aria-label="Scroll left"
                                    onclick="this.parentElement.querySelector('.gr-shopby-chips').scrollBy({ left: -240, behavior: 'smooth' })"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                            <div class="gr-shopby-chips">
                                @foreach ($group->options as $option)
                                    <a href="{{ route('glamroom.home', ['shop_by' => $option->key]) }}"
                                       @class(['gr-chip', 'is-active' => $current === $option->key])>{{ $option->name }}</a>
                                @endforeach
                            </div>
                            <button type="button" class="gr-shopby-arrow" aria-label="Scroll right"
                                    onclick="this.parentElement.querySelector('.gr-shopby-chips').scrollBy({ left: 240, behavior: 'smooth' })"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>
        </dialog>
    @endif

</section>
@endif
