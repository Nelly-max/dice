<div class="info-bar">
    <a href="#" class="info">
        <i class="fa-solid fa-store" style="--icon: #9f1239" aria-hidden="true"></i>
        <div class="info-txt">
            <h3>Liquor stores nearby</h3>
            <h4>Shops within {{ config('liquorhub.max_radius_m') / 1000 }} km</h4>
        </div>
    </a>

    <a href="#hot-deals" class="info">
        <i class="fa-solid fa-tags" style="--icon: #00c2d1" aria-hidden="true"></i>
        <div class="info-txt">
            <h3>Weekly deals</h3>
            <h4>Never miss an offer</h4>
        </div>
    </a>

    <a href="{{ route('page', 'terms') }}" class="info">
        <i class="fa-solid fa-id-card" style="--icon: #00d26a" aria-hidden="true"></i>
        <div class="info-txt">
            <h3>18+ only</h3>
            <h4>ID may be checked on delivery</h4>
        </div>
    </a>
</div>
