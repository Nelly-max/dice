@extends('layouts.main')

@section('title', 'Products · Smart Market')
@section('description', 'Every Smart Market service in one place: shops, homes, gas and more.')

@section('content')
@php
    $mediaBase = rtrim(config('app.media_url'), '/');
@endphp

<div class="sm-page sm-products">
    <header class="sm-settings-head">
        <h1>Products</h1>
        <p>{{ $subdivisions->count() }} {{ \Illuminate\Support\Str::plural('service', $subdivisions->count()) }} on Smart Market. Pick one to start.</p>
    </header>

    @if ($subdivisions->isEmpty())
        <p class="sm-empty">No services are open yet. Please check back soon.</p>
    @else
        <div class="sm-tiles sm-products-grid">
            @foreach ($subdivisions as $sub)
                <a href="{{ route('subdivision.route', $sub->weblink) }}" class="sm-tile">
                    <span class="sm-tile-logo">
                        @if (!empty($sub->logo))
                            <img src="{{ $mediaBase . '/' . ltrim($sub->logo, '/') }}" alt="" loading="lazy">
                        @else
                            <b>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($sub->name, 0, 1)) }}</b>
                        @endif
                    </span>
                    <span class="sm-tile-text">
                        <span class="sm-tile-name">{{ $sub->name }}</span>
                        <small class="sm-tile-meta">{{ $sub->division_name }}</small>
                    </span>
                    <i class="fa-solid fa-chevron-right sm-tile-go" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
