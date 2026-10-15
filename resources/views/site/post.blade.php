@extends('site.layout')

@php
    $pageTitle = ($post['title'] ?? '').' - Pārdod Laimīgs';
    $pageDescription = $post['excerpt'] ?? '';
@endphp

@section('content')
    <article class="section post-page">
        <div class="container post-layout">
            <p class="breadcrumb"><a href="{{ \App\Support\SiteUrls::path($locale, '/blog/') }}">{{ $copy['navBlog'] ?? '' }}</a><span aria-hidden="true">/</span><span>{{ $post['title'] }}</span></p>
            <h1>{{ $post['title'] }}</h1>
            <p class="post-date">{{ \Illuminate\Support\Carbon::parse($post['date'])->format('d.m.Y') }}</p>
            @if (! empty($post['image']))
                <img class="post-cover" src="{{ $post['image'] }}" alt="{{ $post['image_alt'] ?? '' }}">
            @endif
            <div class="post-body">
                {!! $post['html'] !!}
            </div>
        </div>
    </section>

    @include('site.partials.contact-section', ['locale' => $locale, 'copy' => $copy])
@endsection
