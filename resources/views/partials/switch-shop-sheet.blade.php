{{--
    "Start a new cart?" prompt shared by every subdivision. It opens when the
    cart already holds items from another shop in the same subdivision (cart.js: confirmReplaceCart).
    Items from other subdivisions are not touched.
--}}
<div class="switch-shop" id="switchShopSheet" hidden>
    <div class="switch-shop-overlay" data-switch-shop="keep"></div>

    <div class="switch-shop-card" role="dialog" aria-modal="true" aria-labelledby="switchShopTitle">
        <h3 id="switchShopTitle">Start a new cart?</h3>
        <p>Your cart can hold items from different sections, but only one shop per section (<span id="switchShopSection">this section</span>). Adding this item replaces that shop's items; the rest of your cart stays.</p>

        <dl>
            <div>
                <dt>Current cart</dt>
                <dd id="switchShopCurrent">-</dd>
            </div>
            <div class="new">
                <dt>New item from</dt>
                <dd id="switchShopNew">-</dd>
            </div>
        </dl>

        <button type="button" class="primary" data-switch-shop="replace">Replace those items</button>
        <button type="button" class="secondary" data-switch-shop="keep">Keep my current cart</button>
    </div>
</div>
