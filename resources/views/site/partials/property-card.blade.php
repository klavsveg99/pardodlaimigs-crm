@php
    /**
     * Shared property card. Accepts both data shapes: the preview snapshot
     * (image_local/gallery/hero_image) and the live CRM feed (image/gallery).
     */
    $image = $property['image_local'] ?? $property['image'] ?? '';
    $price = $property['price'] ?? '';
    $priceValue = (int) preg_replace('/[^0-9]/', '', (string) $price);
    $category = $property['category'] ?? '';
    $categoryLabel = $copy['categoryLabels'][$category] ?? $category;
    $facts = collect($property['facts'] ?? [])->take(3)->map(fn ($fact) => [
        'value' => $fact['value'],
        'label' => $copy['factLabels'][$fact['label']] ?? $fact['label'],
    ]);
    $city = $property['city'] ?? '';
@endphp
<article class="property-card"
         data-property="{{ $property['slug'] }}"
         data-category="{{ $category }}"
         data-price="{{ $priceValue }}"
         data-search="{{ $property['title'].' '.($property['address'] ?? '').' '.($property['description'] ?? '') }}">
    <button class="property-image-button" type="button" data-property="{{ $property['slug'] }}" aria-label="{{ ($copy['details'] ?? 'Apskatīt īpašumu').': '.$property['title'] }}">
        <img src="{{ $image }}" alt="{{ $property['title'] }}" loading="lazy">
        @if ($categoryLabel)<span class="property-category">{{ $categoryLabel }}</span>@endif
        @if (! empty($property['gallery_count']))<span class="photo-count">{{ $property['gallery_count'] }} {{ $copy['photos'] ?? '' }}</span>@endif
    </button>
    <div class="property-card-body">
        <div class="property-card-top">
            <span class="property-area-label">{{ $city }}</span>
            @if (! empty($property['agent']))<span class="property-agent-label">{{ $property['agent'] }}</span>@endif
        </div>
        <h3><button class="property-title-button" type="button" data-property="{{ $property['slug'] }}">{{ $property['title'] }}</button></h3>
        <p class="property-address">{{ $property['address'] ?? '' }}</p>
        @if ($facts->isNotEmpty())
            <div class="property-facts">
                @foreach ($facts as $fact)
                    <span>{{ $fact['value'] }} <small>{{ $fact['label'] }}</small></span>
                @endforeach
            </div>
        @endif
        <div class="property-card-bottom">
            <strong class="property-price">{{ $price !== '' ? $price : ($copy['priceFrom'] ?? '') }}</strong>
            <a class="arrow-button" href="{{ \App\Support\SiteUrls::property($locale, $property['slug']) }}" aria-label="{{ $copy['details'] ?? '' }}">↗</a>
        </div>
    </div>
</article>
