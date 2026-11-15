{{--
    Header card for the Hub's application and edit pages (rider, marketer, account):
    a coloured icon, title, one line, and optional steps of what the form asks for.

    @include('partials.Hub.page-hero', [
        'icon' => 'fa-solid fa-motorcycle', 'title' => 'Become a rider', 'text' => '…',
        'steps' => ['Your details', 'Vehicle', 'Documents'],   // optional
        'tone' => 'rider',                                    // rider | marketer | account (colour)
        'badge' => 'Active',                                  // optional pill beside the title
        'back' => route('hub.account.index'),                 // optional: a Back button (history first)
        'links' => [['href' => route('…'), 'icon' => 'fa-solid fa-user-pen', 'label' => 'Edit']],   // optional
    ])
--}}
<section @class(['account-detail', 'hub-hero', 'hub-hero--' . ($tone ?? 'account')]) aria-labelledby="hubHeroTitle">
    <div class="hub-hero-icon" aria-hidden="true"><i class="{{ $icon ?? 'fa-solid fa-user' }}"></i></div>
    <div class="hub-hero-text">
        <h1 id="hubHeroTitle">{{ $title }}@if (!empty($badge))<em class="hub-hero-badge">{{ $badge }}</em>@endif</h1>
        @if (!empty($text))<p>{{ $text }}</p>@endif
    </div>
    @if (!empty($steps))
        <ol class="hub-hero-steps" aria-label="What you will fill in">
            @foreach ($steps as $i => $step)
                <li><b>{{ $i + 1 }}</b>{{ $step }}</li>
            @endforeach
        </ol>
    @endif
    @if (!empty($back) || !empty($links))
        <div class="hub-hero-actions">
            @if (!empty($back))
                @include('partials.Hub.back', ['fallback' => $back, 'class' => 'hub-hero-btn'])
            @endif
            @foreach ($links ?? [] as $link)
                <a href="{{ $link['href'] }}" class="hub-hero-btn is-solid"><i class="{{ $link['icon'] ?? 'fa-solid fa-arrow-right' }}" aria-hidden="true"></i> {{ $link['label'] }}</a>
            @endforeach
        </div>
    @endif
</section>
