<?php

declare(strict_types=1);

namespace App\Services\Wp;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Normalises WordPress property feed items (wp-json/crm/v1/properties)
 * into the shape crm.properties_cache expects.
 *
 * The WP feed is the source of truth; `id` is the unique external WP
 * property ID and `updated_at` drives change detection.
 */
class PropertyNormalizer
{
    /**
     * @param  object  $wp  Feed item (id, title, url, status, price, …).
     */
    public function normalize(object $wp): array
    {
        // Handle new mu-plugin format (has 'ID' and 'post_title')
        if (isset($wp->ID) && isset($wp->post_title)) {
            return $this->normalizeMuPlugin($wp);
        }

        // Handle old format
        return $this->normalizeLegacy($wp);
    }

    private function normalizeMuPlugin(object $wp): array
    {
        $title = $this->clean((string) ($wp->post_title ?? '')) ?: '—';
        $meta = (array) ($wp->meta ?? []);
        $terms = (array) ($wp->terms ?? []);

        return [
            'id' => (int) $wp->ID,
            'title' => $title,
            'slug' => Str::slug($title) ?: (string) $wp->ID,
            'status' => $this->clean((string) ($wp->post_status ?? '')) ?: 'publish',
            'category' => $this->firstTermName($terms['property-status'] ?? []),
            'price_cents' => $this->money($meta['ere_property_price'] ?? null),
            'currency' => strtoupper($this->clean((string) ($meta['ere_property_price_prefix'] ?? ''))) ?: 'EUR',
            'beds' => $this->intOrNull($meta['ere_property_bedrooms'] ?? null),
            'baths' => $this->intOrNull($meta['ere_property_bathrooms'] ?? null),
            'size_m2' => $this->num($meta['ere_property_area'] ?? null),
            'land_m2' => $this->toSquareMeters($meta['ere_property_land_area'] ?? null, $meta['ere_property_land_area_unit'] ?? null),
            'lat' => $this->num($meta['ere_property_latitude'] ?? null),
            'lng' => $this->num($meta['ere_property_longitude'] ?? null),
            'country' => $this->clean((string) ($meta['ere_property_country'] ?? '')) ?: null,
            'state' => $this->clean((string) ($meta['ere_property_state'] ?? '')) ?: null,
            'city' => $this->clean((string) ($meta['ere_property_city'] ?? '')) ?: null,
            'neighborhood' => $this->clean((string) ($meta['ere_property_neighborhood'] ?? '')) ?: null,
            'address' => $this->clean((string) ($meta['ere_property_address'] ?? '')) ?: null,
            'kadastra_nr' => $this->clean((string) ($meta['kadastra_nr'] ?? $meta['kadastra_numurs'] ?? $meta['ere_property_cadastral_number'] ?? '')) ?: null,
            'type_ids' => $this->termIds($terms['property-type'] ?? []),
            'feature_ids' => $this->termIds($terms['property-feature'] ?? []),
            'label_ids' => $this->termIds($terms['property-label'] ?? []),
            'thumbnail_url' => $this->primaryImageFromMeta($meta),
            'gallery_urls' => $this->galleryUrlsFromMeta($meta),
            'agent_wp_user_id' => null,
            'agency_wp_term_id' => null,
            'wp_permalink' => config('wp-bridge.wordpress.site_url').'/ipasums/'.($wp->post_name ?? $wp->ID).'/',
            'wp_updated_at' => $this->dt($wp->post_modified ?? null),
            'cached_at' => now(),
        ];
    }

    private function normalizeLegacy(object $wp): array
    {
        $title = $this->clean((string) ($wp->title ?? '')) ?: '—';

        return [
            'id' => (int) $wp->id,
            'title' => $title,
            'slug' => Str::slug($title) ?: (string) $wp->id,
            'status' => $this->clean((string) ($wp->status ?? '')) ?: 'publish',
            'category' => $this->firstTermName($wp->property_status ?? []),
            'price_cents' => $this->money($wp->price ?? null),
            'currency' => strtoupper($this->clean((string) ($wp->price_unit ?? ''))) ?: 'EUR',
            'beds' => $this->intOrNull($wp->bedrooms ?? null),
            'baths' => $this->intOrNull($wp->bathrooms ?? null),
            'size_m2' => $this->num($wp->area ?? null),
            'land_m2' => $this->toSquareMeters($wp->lot_area ?? null, $wp->lot_area_unit ?? null),
            'lat' => $this->num($wp->latitude ?? null),
            'lng' => $this->num($wp->longitude ?? null),
            'country' => null,
            'state' => null,
            'city' => null,
            'neighborhood' => null,
            'address' => $this->clean((string) ($wp->address ?? '')) ?: null,
            'kadastra_nr' => $this->clean((string) ($wp->kadastra_nr ?? $wp->kadastra_numurs ?? $wp->cadastral_number ?? '')) ?: null,
            'type_ids' => $this->termIds($wp->property_type ?? []),
            'feature_ids' => [],
            'label_ids' => [],
            'thumbnail_url' => $this->primaryImage($wp->featured_image ?? null, $wp->images ?? []),
            'gallery_urls' => $this->galleryUrls($wp->images ?? []),
            'agent_wp_user_id' => null,
            'agency_wp_term_id' => null,
            'wp_permalink' => $this->clean((string) ($wp->url ?? '')) ?: null,
            'wp_updated_at' => $this->dt($wp->updated_at ?? null),
            'cached_at' => now(),
        ];
    }

    private function clean(string $value): string
    {
        return html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function money(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $clean = preg_replace('/[^\d.,-]/', '', (string) $value);
        if ($clean === null || $clean === '' || $clean === '-' || $clean === '.') {
            return null;
        }

        if (str_contains($clean, ',') && ! str_contains($clean, '.')) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        $float = (float) $clean;
        if ($float <= 0) {
            return null;
        }

        return (int) round($float * 100);
    }

    private function num(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Convert a lot/land size into square metres. ERE stores land in m² by
     * default but allows ha; normalise to m² so the CRM column stays clean.
     */
    private function toSquareMeters(mixed $value, mixed $unit): ?float
    {
        $size = $this->num($value);
        if ($size === null) {
            return null;
        }

        $unit = strtolower(trim((string) $unit));

        return str_contains($unit, 'ha') ? $size * 10000 : $size;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function dt(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<array{id: int, name: string, slug: string}>  $terms
     * @return array<int>
     */
    private function termIds(array $terms): array
    {
        $ids = [];
        foreach ($terms as $term) {
            $term = (array) $term;
            $ids[] = (int) ($term['id'] ?? 0);
        }

        return array_values(array_filter($ids, fn (int $id): bool => $id > 0));
    }

    /**
     * @param  array<array{id: int, name: string, slug: string}>  $terms
     */
    private function firstTermName(array $terms): ?string
    {
        foreach ($terms as $term) {
            $term = (array) $term;
            $name = $this->clean((string) ($term['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private function primaryImage(mixed $featured, array $images): ?string
    {
        $featured = $this->clean((string) $featured);
        if ($featured !== '') {
            return $featured;
        }

        return $this->galleryUrls($images)[0] ?? null;
    }

    /**
     * @return array<string>
     */
    private function galleryUrls(array $images): array
    {
        $urls = [];
        foreach ($images as $image) {
            $image = (array) $image;
            $url = $this->clean((string) ($image['url'] ?? ''));
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    private function primaryImageFromMeta(array $meta): ?string
    {
        $featuredId = $meta['_thumbnail_id'] ?? $meta['ere_property_gallery'] ?? null;
        if ($featuredId && is_numeric($featuredId)) {
            $gallery = $this->galleryUrlsFromMeta($meta);

            return $gallery[0] ?? null;
        }

        return null;
    }

    private function galleryUrlsFromMeta(array $meta): array
    {
        $gallery = $meta['ere_property_gallery'] ?? null;
        if (! $gallery) {
            return [];
        }

        if (is_string($gallery)) {
            $ids = array_filter(explode(',', $gallery), 'is_numeric');
        } elseif (is_array($gallery)) {
            $ids = $gallery;
        } else {
            return [];
        }

        $urls = [];
        foreach ($ids as $id) {
            $key = 'ere_property_gallery_'.trim($id);
            if (isset($meta[$key]) && is_string($meta[$key]) && $meta[$key] !== '') {
                $urls[] = $meta[$key];
            }
        }

        return $urls;
    }
}
