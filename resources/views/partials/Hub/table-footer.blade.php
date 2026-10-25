{{--
    Table footer: "Showing x–y of z" + page numbers.
    @include('partials.Hub.table-footer', ['paginator' => $orders, 'label' => 'orders'])
--}}
@php $label = $label ?? 'entries'; @endphp

<div class="hub-table-foot">
    <p class="hub-table-info">
        @if ($paginator->total() > 0)
            Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> {{ $label }}
        @else
            No {{ $label }} to show
        @endif
    </p>

    @if ($paginator->hasPages())
        @php
            $current = $paginator->currentPage();
            $last = $paginator->lastPage();

            if ($last <= 7) {
                $pages = range(1, $last);
            } elseif ($current <= 4) {
                $pages = [1, 2, 3, 4, 5, '…', $last];
            } elseif ($current >= $last - 3) {
                $pages = [1, '…', $last - 4, $last - 3, $last - 2, $last - 1, $last];
            } else {
                $pages = [1, '…', $current - 1, $current, $current + 1, '…', $last];
            }
        @endphp

        <nav class="hub-pgr" aria-label="Pages">
            @if ($paginator->onFirstPage())
                <span class="hub-pg is-disabled" aria-hidden="true"><i class="fa-solid fa-angle-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="hub-pg" aria-label="Previous page"><i class="fa-solid fa-angle-left"></i></a>
            @endif

            @foreach ($pages as $page)
                @if ($page === '…')
                    <span class="hub-pg is-gap">…</span>
                @elseif ($page == $current)
                    <span class="hub-pg is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $paginator->url($page) }}" class="hub-pg">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="hub-pg" aria-label="Next page"><i class="fa-solid fa-angle-right"></i></a>
            @else
                <span class="hub-pg is-disabled" aria-hidden="true"><i class="fa-solid fa-angle-right"></i></span>
            @endif
        </nav>
    @endif
</div>
