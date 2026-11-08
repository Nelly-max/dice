{{--
    "How far from me?" bar for Home City and Airbnb.
    Expects:
      $hasLocation  bool         visitor saved a location (map sheet, same as the shops)
      $radius       int|null     chosen radius in metres, null = any distance
      $counts       array        [metres => number of listings within it, 'any' => total]
      $noun         string       "home", "stay"…
    Links keep the other query parameters (?type=, ?q=) and set ?radius=.
--}}
@php
    $nounPlural = \Illuminate\Support\Str::plural($noun);
@endphp

@if (!$hasLocation)
    <div class="context-banner radius-banner" role="status">
        <i class="fa-solid fa-location-dot"></i>
        <p><strong>See {{ $nounPlural }} near you.</strong> Set your location to show {{ $nounPlural }} within 500 m, 1 km, 2 km, 3 km or 5 km.</p>
        <button type="button" onclick="showModal('mapModal')"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Set location</button>
    </div>
@else
    <section class="radius-bar" aria-label="Distance from your location">
        <div class="radius-bar-head">
            <span class="radius-bar-pin" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
            <div>
                <small>Showing {{ $nounPlural }}</small>
                <strong>{{ $radius ? 'within ' . \App\Support\Distance::label($radius) . ' of you' : 'at any distance' }}</strong>
            </div>
            <button type="button" class="radius-bar-change" onclick="showModal('mapModal')">Change location</button>
        </div>

        <div class="radius-chips" role="group" aria-label="Distance">
            @foreach (\App\Support\Distance::RADII as $m)
                <a href="{{ request()->fullUrlWithQuery(['radius' => $m]) }}"
                   @class(['radius-chip', 'is-active' => $radius === $m])
                   @if ($radius === $m) aria-current="true" @endif>
                    {{ \App\Support\Distance::label($m) }} <b>{{ $counts[$m] ?? 0 }}</b>
                </a>
            @endforeach
            <a href="{{ request()->fullUrlWithQuery(['radius' => 'any']) }}"
               @class(['radius-chip', 'is-active' => $radius === null])
               @if ($radius === null) aria-current="true" @endif>
                Any <b>{{ $counts['any'] ?? 0 }}</b>
            </a>
        </div>
    </section>
@endif
