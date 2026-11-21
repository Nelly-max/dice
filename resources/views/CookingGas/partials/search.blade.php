{{-- Live search results (ProductController@search, AJAX). services-ui.js puts this in the header dropdown. --}}
@forelse ($cylinders as $cylinder)
    @php
        $brand = $cylinder->cylinder->brand_name ?? 'Gas';
        $size = trim((string) ($cylinder->quantity->quantity ?? ''));
        $sizeLabel = is_numeric($size) ? $size . ' kg' : $size;
    @endphp
    <a href="{{ route('gas.cylinder.view', ['cylinder' => $cylinder->gas_cylinder_id, 'quantity' => $cylinder->gas_quantity_id]) }}" class="sv-result">
        <img src="{{ $cylinder->image ? rtrim(config('app.media_url'), '/') . '/' . ltrim($cylinder->image, '/') : asset('img/placeholder.png') }}" alt="">
        <span>
            <strong>{{ $brand }}@if ($sizeLabel !== '') · {{ $sizeLabel }}@endif</strong>
            <small>
                @if ($cylinder->min_price == $cylinder->max_price)
                    KES {{ number_format($cylinder->min_price) }}
                @else
                    KES {{ number_format($cylinder->min_price) }} – {{ number_format($cylinder->max_price) }}
                @endif
            </small>
        </span>
    </a>
@empty
    <p class="sv-result-empty">No gas found for “{{ request('term') }}”.</p>
@endforelse
