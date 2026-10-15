@extends('site.layout')

@php
    $pageTitle = $agent['name'].' - Pārdod Laimīgs';
    $pageDescription = \Illuminate\Support\Str::limit(trim((string) ($agent['description'] ?? '')), 160);
    $properties = collect($agent['properties'] ?? []);
@endphp

@section('content')
    @if ($properties->isNotEmpty())
        @include('site.partials.property-dialog-data', ['properties' => $properties, 'locale' => $locale, 'copy' => $copy])
    @endif

    <section class="section agent-page">
        <div class="container agent-layout">
            @if (! empty($agent['avatar_url']))
                <img class="agent-portrait" src="{{ $agent['avatar_url'] }}" alt="{{ $agent['name'] }}">
            @endif
            <div class="agent-copy">
                <p class="eyebrow eyebrow-green">{{ $agent['position'] ?? ($copy['agent'] ?? '') }}</p>
                <h1>{{ $agent['name'] }}</h1>
                @if (! empty($agent['description']))
                    <div class="agent-description">{!! nl2br(e($agent['description'])) !!}</div>
                @endif
                <div class="contact-direct">
                    @if (! empty($agent['phone']))
                        <a href="tel:{{ preg_replace('/\s/', '', $agent['phone']) }}"><span class="contact-label">{{ $copy['call'] ?? '' }}</span><strong>{{ $agent['phone'] }}</strong></a>
                    @endif
                    @if (! empty($agent['email']))
                        <a href="mailto:{{ $agent['email'] }}"><span class="contact-label">{{ $copy['email'] ?? '' }}</span><strong>{{ $agent['email'] }}</strong></a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if ($properties->isNotEmpty())
        <section class="section properties-section" aria-labelledby="agent-properties-title">
            <div class="container">
                <div class="section-heading">
                    <p class="eyebrow eyebrow-green">{{ $copy['propertiesEyebrow'] ?? '' }}</p>
                    <h2 id="agent-properties-title">{{ $copy['navProperties'] ?? '' }}</h2>
                </div>
                <div class="property-grid" id="property-grid" data-limit="0">
                    @foreach ($properties as $property)
                        @include('site.partials.property-card', ['property' => $property, 'locale' => $locale, 'copy' => $copy])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('site.partials.contact-section', ['locale' => $locale, 'copy' => $copy])
@endsection
