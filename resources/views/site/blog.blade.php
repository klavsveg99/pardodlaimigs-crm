@extends('site.layout')

@php
    $pageTitle = ($copy['blogTitle'] ?? 'Blog').' - Pārdod Laimīgs';
@endphp

@section('content')
    <section class="section" aria-labelledby="blog-title">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow eyebrow-green">{{ $copy['blogEyebrow'] ?? '' }}</p>
                <h2 id="blog-title">{{ $copy['blogTitle'] ?? '' }}</h2>
            </div>
            <div class="article-grid blog-index">
                @foreach ($posts as $index => $post)
                    <article class="article-card">
                        <a href="{{ \App\Support\SiteUrls::post($locale, $post['slug']) }}">
                            @if (! empty($post['image']))<img src="{{ $post['image'] }}" alt="" loading="lazy">@endif
                            <div class="article-copy">
                                <span class="article-date">{{ \Illuminate\Support\Carbon::parse($post['date'])->format('d.m.Y') }}</span>
                                <h3>{{ $copy['articleTitles'][$index] ?? $post['title'] }}</h3>
                                <span class="article-link">{{ $copy['readArticle'] ?? '' }}<span aria-hidden="true">↗</span></span>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection
