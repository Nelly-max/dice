@extends('layouts.main')

@section('title', $page['title'] . ' · Smart Market')
@section('description', $page['summary'] ?? config('smartmarket.tagline'))

@section('content')
<div class="sm-page sm-doc-page">

    <a href="{{ route('setting') }}" class="sm-back-link">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Settings
    </a>

    <div class="sm-doc-layout">
        <article class="sm-doc">
            <header class="sm-doc-head">
                <span class="sm-doc-icon" aria-hidden="true"><i class="{{ $page['icon'] }}"></i></span>
                <div>
                    <h1>{{ $page['title'] }}</h1>
                    @if ($updated)
                        <p class="sm-doc-date">Last updated {{ $updated->format('j F Y') }}</p>
                    @endif
                </div>

                {{-- Web Share on phones, copy link elsewhere (smart-home.js) --}}
                <button type="button" class="sm-share" data-share
                        data-share-title="{{ $page['title'] }} · Smart Market"
                        data-share-url="{{ route('page', $slug) }}">
                    <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                    <span>Share</span>
                </button>
            </header>

            <div class="sm-prose">
                @include("pages.{$slug}")
            </div>
        </article>

        <aside class="sm-doc-menu" aria-label="More information">
            <h2>More information</h2>
            <nav>
                @foreach ($pages as $key => $item)
                    <a href="{{ route('page', $key) }}" @class(['active' => $key === $slug])>
                        <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['menu'] }}</span>
                        <i class="fa-solid fa-chevron-right sm-go" aria-hidden="true"></i>
                    </a>
                @endforeach
            </nav>
        </aside>
    </div>
</div>
@endsection
