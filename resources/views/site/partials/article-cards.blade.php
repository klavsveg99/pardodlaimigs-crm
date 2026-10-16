@php
    // Shared by the homepage's first batch and the load more endpoint, so both
    // render exactly the same card. $offset keeps the hand written titles
    // (articleTitles) aligned with the first three posts.
    $offset = $offset ?? 0;
@endphp
@foreach ($posts as $index => $post)
    <article class="article-card">
        <a href="{{ \App\Support\SiteUrls::post($locale, $post['slug']) }}">
            @if (filled($post['image'] ?? null))
                <img src="{{ $post['image'] }}" alt="" loading="lazy">
            @else
                {{-- WordPress posts that never had a featured image. --}}
                <div class="article-placeholder" aria-hidden="true"><img src="{{ asset('images/favicon-180x180.jpg') }}" alt=""></div>
            @endif
            <div class="article-copy">
                <span class="article-date">{{ \Illuminate\Support\Carbon::parse($post['date'])->format('d.m.Y') }}</span>
                <h3>{{ $copy['articleTitles'][$offset + $index] ?? $post['title'] }}</h3>
                <span class="article-link">{{ $copy['readArticle'] ?? '' }}<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
            </div>
        </a>
    </article>
@endforeach
