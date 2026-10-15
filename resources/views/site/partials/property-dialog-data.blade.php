@php
    /** Property list handed to the dialog as JSON (same fields the cards show). */
    $dialogProperties = collect($properties)->map(function (array $property) use ($locale, $copy) {
        $gallery = $property['gallery'] ?? [];
        if ($gallery === [] && ! empty($property['image_local'])) {
            $gallery = [$property['image_local']];
        }
        if ($gallery === [] && ! empty($property['image'])) {
            $gallery = [$property['image']];
        }

        return [
            'slug' => $property['slug'],
            'title' => $property['title'],
            'address' => $property['address'] ?? ($property['city'] ?? ''),
            'city' => $property['city'] ?? '',
            'price' => $property['price'] ?? '',
            'category_label' => $copy['categoryLabels'][$property['category'] ?? ''] ?? ($property['category'] ?? ''),
            'gallery' => array_values($gallery),
            'lat' => $property['lat'] ?? null,
            'lng' => $property['lng'] ?? null,
            'facts' => collect($property['facts'] ?? [])->map(fn ($fact) => [
                'value' => $fact['value'],
                'label' => $copy['factLabels'][$fact['label']] ?? $fact['label'],
            ])->values()->all(),
            'summary' => \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', preg_replace('/^\[LV\]/', '', (string) ($property['description'] ?? '')))), 320),
            'url' => \App\Support\SiteUrls::property($locale, $property['slug']),
            'label' => ($copy['details'] ?? '').': '.$property['title'],
            'details_label' => $copy['fullListing'] ?? '',
            'photos_label' => $copy['photos'] ?? '',
            'map_label' => $copy['preciseLocation'] ?? '',
            'maps_label' => $copy['openInMaps'] ?? '',
        ];
    })->values()->all();
@endphp
<script type="application/json" id="site-properties">@json($dialogProperties)</script>
