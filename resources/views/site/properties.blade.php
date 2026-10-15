@extends('site.layout')

@php
    $pageTitle = ($copy['propertiesTitle'] ?? 'Īpašumi').' - Pārdod Laimīgs';
@endphp

@section('content')
    @include('site.partials.property-dialog-data', ['properties' => $properties, 'locale' => $locale, 'copy' => $copy])

    <section class="section properties-section listings-page" id="ipasumi" aria-labelledby="properties-title">
        <div class="container">
            <div class="section-heading section-heading-row">
                <div>
                    <p class="eyebrow eyebrow-green">{{ $copy['propertiesEyebrow'] ?? '' }}</p>
                    <h2 id="properties-title">{{ $copy['propertiesTitle'] ?? '' }}</h2>
                    <p class="section-lead">{{ $copy['propertiesLead'] ?? '' }}</p>
                </div>
            </div>
            <form id="property-search" class="search-controls listings-filters">
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
            <div class="results-line">
                <span id="property-count" data-suffix="{{ $copy['propertyCount'] ?? '' }}">{{ count($properties) }} {{ $copy['propertyCount'] ?? '' }}</span>
                <span id="filter-summary" data-empty="{{ $copy['noResults'] ?? '' }}"></span>
            </div>
            <div class="property-grid" id="property-grid" data-limit="0" aria-live="polite">
                @foreach ($properties as $property)
                    @include('site.partials.property-card', ['property' => $property, 'locale' => $locale, 'copy' => $copy])
                @endforeach
            </div>
        </div>
    </section>
@endsection
