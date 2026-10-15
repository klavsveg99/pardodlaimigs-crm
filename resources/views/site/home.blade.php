@extends('site.layout')

@php
    $pageTitle = 'Pārdod Laimīgs - '.($copy['heroTitle'] ?? '');
    $pageDescription = $copy['heroBody'] ?? '';
    $heroSlides = collect($heroProperties);
    $cardProperties = collect($properties)->take(6);
@endphp

@section('content')
    @include('site.partials.property-dialog-data', ['properties' => $properties, 'locale' => $locale, 'copy' => $copy])

    <section class="hero-section">
        <div class="container hero-layout">
            <div class="hero-copy">
                <p class="eyebrow">{{ $copy['eyebrow'] ?? '' }}</p>
                <h1>{!! nl2br(e($copy['heroTitle'] ?? '')) !!}</h1>
                <p class="hero-description">{{ $copy['heroBody'] ?? '' }}</p>
                <div class="hero-actions">
                    <a class="button button-light" href="#kontakti"><span>{{ $copy['heroAction'] ?? '' }}</span><span aria-hidden="true">↗</span></a>
                    <button class="video-link" type="button" id="open-video" aria-haspopup="dialog"><span class="play-icon" aria-hidden="true">▶</span><span>{{ $copy['videoAction'] ?? '' }}</span></button>
                </div>
                <div class="hero-proof"><span class="proof-stars" aria-hidden="true">★★★★★</span><span>{{ $copy['heroNote'] ?? '' }}</span></div>
            </div>
            <div class="hero-visual" id="hero-carousel" aria-roledescription="carousel" aria-label="{{ $copy['heroCarouselLabel'] ?? '' }}">
                <div class="hero-slides">
                    @foreach ($heroSlides as $index => $property)
                        @php
                            $image = $property['hero_image'] ?? ($property['image_local'] ?? ($property['image'] ?? ''));
                            // Built here rather than inline in @json(): the
                            // directive's parser stops at a nested parenthesis.
                            $slide = [
                                'slug' => $property['slug'],
                                'title' => $property['title'],
                                'city' => $property['city'] ?? '',
                                'price' => $property['price'] ?? '',
                                'label' => ($copy['details'] ?? '').': '.$property['title'],
                            ];
                        @endphp
                        <img class="hero-slide{{ $index === 0 ? ' is-active' : '' }}" src="{{ $image }}" alt="{{ $property['title'] }}" @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif>
                        <script type="application/json" data-hero-slide>@json($slide)</script>
                    @endforeach
                    <button class="hero-slide-link" type="button" data-hero-link aria-label=""></button>
                </div>
                <div class="hero-image-caption"><span class="caption-label" id="hero-caption-title"></span><span class="caption-location" id="hero-caption-location"></span></div>
                <div class="hero-controls">
                    <button class="hero-control" type="button" data-hero-prev aria-label="{{ $copy['previousSlide'] ?? '' }}">←</button>
                    <button class="hero-control" type="button" data-hero-next aria-label="{{ $copy['nextSlide'] ?? '' }}">→</button>
                </div>
                <div class="hero-index"><span id="hero-position">01</span><span class="hero-index-line"></span><span id="hero-total"></span></div>
                <div class="hero-progress" aria-hidden="true"><span id="hero-progress"></span></div>
            </div>
        </div>
    </section>

    <section class="search-section" aria-labelledby="search-title">
        <div class="container search-panel">
            <div class="search-heading"><span class="search-mark" aria-hidden="true"></span><h2 id="search-title">{{ $copy['searchTitle'] ?? '' }}</h2></div>
            <form id="property-search" class="search-controls">
                <label>
                    <span>{{ $copy['searchType'] ?? '' }}</span>
                    <select name="category" id="search-category">
                        <option value="">{{ $copy['allTypes'] ?? '' }}</option>
                        @foreach (collect($properties)->pluck('category')->filter()->unique()->sort() as $category)
                            <option value="{{ $category }}">{{ $copy['categoryLabels'][$category] ?? $category }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>{{ $copy['searchPrice'] ?? '' }}</span>
                    <select name="price" id="search-price">
                        <option value="">{{ $copy['allPrices'] ?? '' }}</option>
                        @foreach ([50000, 100000, 250000, 500000] as $limit)
                            <option value="{{ $limit }}">€{{ number_format($limit, 0, '.', ' ') }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="search-keyword"><span>{{ $copy['searchPlace'] ?? '' }}</span><input name="query" id="search-query" type="search" autocomplete="off"></label>
                <button class="button button-dark" type="submit"><span>{{ $copy['searchAction'] ?? '' }}</span><span aria-hidden="true">↗</span></button>
            </form>
        </div>
    </section>

    <section class="section properties-section" id="ipasumi" aria-labelledby="properties-title">
        <div class="container">
            <div class="section-heading section-heading-row">
                <div>
                    <p class="eyebrow eyebrow-green">{{ $copy['propertiesEyebrow'] ?? '' }}</p>
                    <h2 id="properties-title">{{ $copy['propertiesTitle'] ?? '' }}</h2>
                    <p class="section-lead">{{ $copy['propertiesLead'] ?? '' }}</p>
                </div>
                <a class="text-link desktop-link" href="{{ \App\Support\SiteUrls::path($locale, '/properties/') }}"><span>{{ $copy['viewAll'] ?? '' }}</span><span aria-hidden="true">↗</span></a>
            </div>
            <div class="results-line">
                <span id="property-count" data-suffix="{{ $copy['propertyCount'] ?? '' }}">{{ count($properties) }} {{ $copy['propertyCount'] ?? '' }}</span>
                <span id="filter-summary" data-empty="{{ $copy['noResults'] ?? '' }}"></span>
            </div>
            <div class="property-grid" id="property-grid" data-limit="6" aria-live="polite">
                @foreach ($cardProperties as $property)
                    @include('site.partials.property-card', ['property' => $property, 'locale' => $locale, 'copy' => $copy])
                @endforeach
                @foreach (collect($properties)->slice(6) as $property)
                    @include('site.partials.property-card', ['property' => $property, 'locale' => $locale, 'copy' => $copy])
                @endforeach
            </div>
            <div class="section-action"><button class="button button-outline" id="view-all" type="button"><span>{{ $copy['viewAll'] ?? '' }}</span><span aria-hidden="true">↗</span></button></div>
        </div>
    </section>

    <section class="section selling-section" id="pardot" aria-labelledby="selling-title">
        <div class="container">
            <div class="section-heading section-heading-centered">
                <p class="eyebrow eyebrow-green">{{ $copy['sellEyebrow'] ?? '' }}</p>
                <h2 id="selling-title">{{ $copy['sellTitle'] ?? '' }}</h2>
                <p class="section-lead">{{ $copy['sellLead'] ?? '' }}</p>
            </div>
            <div class="selling-grid">
                @foreach ([['01', 'apartment', 'apartmentText'], ['02', 'house', 'houseText'], ['03', 'land', 'landText']] as [$number, $titleKey, $bodyKey])
                    <article class="selling-item">
                        <span class="selling-number">{{ $number }}</span>
                        <h3>{{ $copy[$titleKey] ?? '' }}</h3>
                        <p>{{ $copy[$bodyKey] ?? '' }}</p>
                        <a class="text-link" href="#kontakti"><span>{{ $copy['sellAction'] ?? '' }}</span><span aria-hidden="true">↗</span></a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section urgent-section" aria-labelledby="urgent-title">
        <div class="container">
            <div class="section-heading section-heading-centered">
                <p class="eyebrow eyebrow-green">{{ $copy['urgentEyebrow'] ?? '' }}</p>
                <h2 id="urgent-title">{{ $copy['urgentTitle'] ?? '' }}</h2>
                <p class="section-lead">{{ $copy['urgentLead'] ?? '' }}</p>
            </div>
            <ol class="urgent-steps">
                @foreach (($copy['urgentSteps'] ?? []) as [$day, $title, $description])
                    <li class="urgent-step"><span class="urgent-day">{{ $day }}</span><h3>{{ $title }}</h3><p>{{ $description }}</p></li>
                @endforeach
            </ol>
            <div class="section-action"><a class="button button-dark" href="#kontakti"><span>{{ $copy['urgentAction'] ?? '' }}</span><span aria-hidden="true">↗</span></a></div>
        </div>
    </section>

    <section class="reviews-section" aria-labelledby="reviews-title">
        <div class="container reviews-layout">
            <div class="reviews-intro">
                <p class="eyebrow">{{ $copy['reviewsEyebrow'] ?? '' }}</p>
                <h2 id="reviews-title">{{ $copy['reviewsTitle'] ?? '' }}</h2>
                <div class="review-score"><span class="proof-stars" aria-hidden="true">★★★★★</span><strong>5/5</strong><span>{{ $copy['reviewCount'] ?? '' }}</span></div>
                <a class="text-link link-light" href="https://g.page/r/CRZd6XZu2hhmEAE/review" target="_blank" rel="noreferrer"><span>{{ $copy['googleReviews'] ?? '' }}</span><span aria-hidden="true">↗</span></a>
            </div>
            <article class="review-card" aria-live="polite">
                <span class="quote-mark" aria-hidden="true">“</span>
                <p id="review-quote"></p>
                <div class="review-author"><strong id="review-name"></strong><span>★★★★★</span></div>
                <div class="review-controls">
                    <button id="review-prev" type="button" aria-label="{{ $copy['previousReview'] ?? '' }}">←</button>
                    <span id="review-position"></span>
                    <button id="review-next" type="button" aria-label="{{ $copy['nextReview'] ?? '' }}">→</button>
                </div>
            </article>
            @foreach (($copy['reviews'] ?? []) as $review)
                <script type="application/json" data-review>@json($review)</script>
            @endforeach
        </div>
    </section>

    <section class="section blog-section" id="blog" aria-labelledby="blog-title">
        <div class="container">
            <div class="section-heading">
                <p class="eyebrow eyebrow-green">{{ $copy['blogEyebrow'] ?? '' }}</p><h2 id="blog-title">{{ $copy['blogTitle'] ?? '' }}</h2>
            </div>
            <div class="article-grid" id="article-grid">
                @include('site.partials.article-cards', ['posts' => $posts, 'copy' => $copy, 'locale' => $locale, 'offset' => 0])
            </div>
            <div class="section-action">
                <button class="button button-outline" id="load-more-posts" type="button"
                        data-endpoint="{{ route('site.blog.more') }}"
                        data-page="2" data-locale="{{ $locale }}"
                        @if (! ($hasMorePosts ?? false)) hidden @endif>{{ $copy['loadMore'] ?? 'Rādīt vairāk' }}</button>
                <p class="load-more-error" id="load-more-error" role="status" hidden>{{ $copy['loadMoreFailed'] ?? '' }}</p>
            </div>
        </div>
    </section>

    @if (filled($useful ?? null))
        <section class="section info-section" aria-labelledby="info-title">
            <div class="container">
                <div class="section-heading"><h2 id="info-title">{{ $useful['title'] }}</h2></div>
                <div class="info-list">
                    @foreach ($useful['tabs'] as $index => $tab)
                        <details class="info-item" @if ($index === 0) open @endif>
                            <summary>{{ $tab['title'] }}</summary>
                            <div class="post-body">{!! $tab['html'] !!}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="podcast-section">
        <div class="container podcast-layout">
            <div><p class="eyebrow">{{ $copy['podcastTitle'] ?? '' }}</p><p>{{ $copy['podcastLead'] ?? '' }}</p></div>
            <div class="podcast-links">
                <a href="https://www.youtube.com/@pardodlaimigs" target="_blank" rel="noreferrer"><span>{{ $copy['youtube'] ?? '' }}</span><span aria-hidden="true">↗</span></a>
                <a href="https://open.spotify.com/show/5Tansq4D8byHA7ciR9DA2n" target="_blank" rel="noreferrer"><span>{{ $copy['spotify'] ?? '' }}</span><span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </section>

    <section class="section team-section" id="komanda" aria-labelledby="team-title">
        <div class="container team-layout">
            <div class="team-heading">
                <p class="eyebrow eyebrow-green">{{ $copy['teamEyebrow'] ?? '' }}</p>
                <h2 id="team-title">{{ $copy['teamTitle'] ?? '' }}</h2>
                <p class="section-lead">{{ $copy['teamLead'] ?? '' }}</p>
            </div>
            <div class="team-grid">
                @foreach ($agents as $agent)
                    <article class="agent-card{{ ($agent['portrait'] ?? false) ? ' agent-card--portrait' : '' }}">
                        @if (! empty($agent['avatar_url']))<img src="{{ $agent['avatar_url'] }}" alt="{{ $agent['name'] }}" loading="lazy">@endif
                        <div>
                            <p class="agent-role">{{ $copy['agent'] ?? '' }}</p>
                            <h3><a href="{{ \App\Support\SiteUrls::agent($locale, $agent['slug']) }}">{{ $agent['name'] }}</a></h3>
                            @if (! empty($agent['phone']))<a href="tel:{{ preg_replace('/\s/', '', $agent['phone']) }}">{{ $agent['phone'] }}</a>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="legal-section" aria-labelledby="legal-title">
        <div class="container">
            <div class="legal-head">
                <p class="eyebrow">{{ $copy['legalEyebrow'] ?? '' }}</p>
                <h2 id="legal-title">{{ $copy['legalTitle'] ?? '' }}</h2>
                <p>{{ $copy['legalLead'] ?? '' }}</p>
            </div>
            <ul class="legal-list">
                @foreach (($copy['legalItems'] ?? []) as $index => $item)
                    <li><span class="legal-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><p>{{ $item }}</p></li>
                @endforeach
            </ul>
        </div>
    </section>

    @include('site.partials.contact-section', ['locale' => $locale, 'copy' => $copy])

    <section class="section faq-section" id="buj" aria-labelledby="faq-title">
        <div class="container faq-layout">
            <div><p class="eyebrow eyebrow-green">{{ $copy['faqEyebrow'] ?? '' }}</p><h2 id="faq-title">{{ $copy['faqTitle'] ?? '' }}</h2></div>
            <div class="faq-list">
                @foreach (($copy['faqItems'] ?? []) as [$question, $answer])
                    <details class="faq-item"><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
                @endforeach
            </div>
        </div>
    </section>
@endsection
