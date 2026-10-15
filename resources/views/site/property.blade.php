@extends('site.layout')

@php
    $images = collect($property['gallery'] ?? [])->filter()->values();
    if ($images->isEmpty()) {
        $images = collect([$property['image_local'] ?? ($property['image'] ?? '')])->filter()->values();
    }
    $facts = collect($property['facts'] ?? [])->map(fn ($fact) => [
        'value' => $fact['value'],
        'label' => $copy['factLabels'][$fact['label']] ?? $fact['label'],
    ]);
    $category = $property['category'] ?? '';
    $categoryLabel = $copy['categoryLabels'][$category] ?? $category;
    $hasLocation = is_numeric($property['lat'] ?? null) && is_numeric($property['lng'] ?? null);
    // The listing text is "[LV]" followed by the prose and then a glued-together
    // parameter dump; the page shows the prose, the facts block the parameters.
    $description = preg_replace('/^\[LV\]/', '', (string) ($property['description'] ?? ''));
    $description = preg_split('/Galvenie parametri/i', $description)[0] ?? $description;
    $description = str_ends_with($description, $property['title']) ? substr($description, 0, -strlen($property['title'])) : $description;
    $description = trim(preg_replace('/\s+/u', ' ', preg_replace('/(?<=[a-zāčēģīķļņšūž0-9])(?=[A-ZĀČĒĢĪĶĻŅŠŪŽ])/u', ' ', $description)));
    $pageTitle = $property['title'].' - Pārdod Laimīgs';
    $pageDescription = \Illuminate\Support\Str::limit($description, 160);
@endphp

@section('content')
    <section class="property-page">
        <div class="container">
            <p class="breadcrumb"><a href="{{ \App\Support\SiteUrls::path($locale, '/properties/') }}">{{ $copy['navProperties'] ?? '' }}</a><span aria-hidden="true">/</span><span>{{ $property['title'] }}</span></p>
            <div class="property-page-layout">
                <div class="property-page-media">
                    <img id="property-detail-image" src="{{ $images->first() }}" alt="{{ $property['title'] }}">
                    @if ($images->count() > 1)
                        <button class="property-nav property-nav-prev" type="button" data-gallery-step="-1" aria-label="{{ $copy['previousSlide'] ?? '' }}">←</button>
                        <button class="property-nav property-nav-next" type="button" data-gallery-step="1" aria-label="{{ $copy['nextSlide'] ?? '' }}">→</button>
                        <span class="property-gallery-position" id="property-gallery-position">1 / {{ $images->count() }}</span>
                    @endif
                </div>
                <div class="property-page-copy">
                    <div class="property-type">
                        @if ($categoryLabel)<span class="property-type-blob">{{ $categoryLabel }}</span>@endif
                        @if ($images->count() > 1)<span class="property-photo-count">{{ $images->count() }} {{ $copy['photos'] ?? '' }}</span>@endif
                    </div>
                    <h1 id="property-dialog-title">{{ $property['title'] }}</h1>
                    <div class="property-detail-place">
                        <p class="property-detail-address">{{ $property['address'] ?? '' }}</p>
                        @if ($hasLocation)
                            <button class="property-map-toggle" type="button" id="property-map-toggle" aria-expanded="false" data-lat="{{ $property['lat'] }}" data-lng="{{ $property['lng'] }}"><span>{{ $copy['preciseLocation'] ?? '' }}</span><span aria-hidden="true">↗</span></button>
                        @endif
                    </div>
                    @if ($hasLocation)
                        <div class="property-map" id="property-map" hidden></div>
                    @endif
                    <p class="property-detail-price">{{ $property['price'] ?? ($copy['priceFrom'] ?? '') }}</p>
                    @if ($facts->isNotEmpty())
                        <ul class="property-detail-facts">
                            @foreach ($facts as $fact)
                                <li><strong>{{ $fact['value'] }}</strong><span>{{ $fact['label'] }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="property-detail-text">{!! nl2br(e($description)) !!}</div>
                    <div class="property-detail-agent">
                        <span>{{ $copy['agent'] ?? '' }}</span>
                        <strong>{{ $property['agent'] ?? '' }}</strong>
                        @if (! empty($property['agent_phone']))<a href="tel:{{ preg_replace('/\s/', '', $property['agent_phone']) }}">{{ $property['agent_phone'] }}</a>@endif
                    </div>
                    <div class="property-detail-actions">
                        <a class="button button-dark" href="#kontakti">{{ $copy['enquire'] ?? '' }}</a>
                    </div>
                </div>
            </div>

            @if ($images->count() > 1)
                <div class="property-page-thumbs" role="group" aria-label="{{ $copy['galleryLabel'] ?? '' }}">
                    @foreach ($images as $index => $image)
                        <button type="button" class="property-thumb{{ $index === 0 ? ' is-active' : '' }}" data-gallery-index="{{ $index }}">
                            <img src="{{ $image }}" alt="" loading="lazy">
                        </button>
                    @endforeach
                </div>
                <script type="application/json" id="site-property-gallery">@json($images)</script>
            @endif
        </div>
    </section>

    @include('site.partials.contact-section', ['locale' => $locale, 'copy' => $copy])
@endsection
