<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\Models\PropertySearch;
use App\Models\ScrapedListing;
use Illuminate\Support\Str;

/**
 * Runs one saved search, filters the portal results against the agent's
 * criteria and stores new ads in the inbox. Deduplication is per agent:
 * the same ad is never stored twice for one user, and an ad that matches
 * the same address/area/price on the other portal is flagged, not merged.
 */
class PropertySearchRunner
{
    public function __construct(
        private readonly SsListingParser $ss,
        private readonly City24ListingParser $city24,
    ) {}

    public function run(PropertySearch $search): int
    {
        $search->update(['last_run_at' => now(), 'last_error' => null]);

        $portals = array_values(array_intersect(
            (array) $search->portals,
            array_keys(PropertySearch::PORTALS),
        ));

        if ($portals === []) {
            return 0;
        }

        $created = 0;
        $errors = [];

        foreach ($portals as $portal) {
            try {
                $items = match ($portal) {
                    'ss' => $this->ss->fetch($search),
                    'city24' => $this->city24->fetch($search),
                    default => [],
                };
            } catch (\Throwable $e) {
                report($e);
                $errors[] = Str::limit($e->getMessage(), 900);

                continue;
            }

            foreach ($items as $item) {
                if (! $this->matches($search, $item, $portal)) {
                    continue;
                }

                $externalId = (string) ($item['external_id'] ?? '');
                $url = (string) ($item['url'] ?? '');
                if ($externalId === '' || $url === '') {
                    continue;
                }

                $fingerprint = $this->fingerprint($item);

                $listing = ScrapedListing::firstOrCreate(
                    [
                        'user_id' => $search->user_id,
                        'portal' => $portal,
                        'external_id' => $externalId,
                    ],
                    [
                        'property_search_id' => $search->id,
                        'url' => $url,
                        'title' => $item['title'] ?? null,
                        'price_eur' => $item['price_eur'] ?? null,
                        'area_m2' => $item['area_m2'] ?? null,
                        'land_m2' => $item['land_m2'] ?? null,
                        'location' => $item['location'] ?? null,
                        'address' => $item['address'] ?? null,
                        'image_url' => $item['image_url'] ?? null,
                        'listed_at' => $item['listed_at'] ?? null,
                        'first_seen_at' => now(),
                        'last_seen_at' => now(),
                        'status' => 'new',
                        'fingerprint' => $fingerprint,
                        'duplicate_of_id' => $fingerprint !== null ? $this->findDuplicate($search, $fingerprint, $portal) : null,
                    ],
                );

                if ($listing->wasRecentlyCreated) {
                    $created++;
                } else {
                    $listing->update([
                        'last_seen_at' => now(),
                        'price_eur' => $item['price_eur'] ?? $listing->price_eur,
                    ]);
                }

                if ($created >= $search->max_results) {
                    break 2;
                }
            }
        }

        if ($errors !== []) {
            $search->update(['last_error' => implode(' | ', $errors)]);
        }

        return $created;
    }

    private function matches(PropertySearch $search, array $item, string $portal): bool
    {
        $price = $item['price_eur'] ?? null;
        if ($price !== null) {
            if ($search->price_min !== null && $price < $search->price_min) {
                return false;
            }
            if ($search->price_max !== null && $price > $search->price_max) {
                return false;
            }
        }

        $area = $item['area_m2'] ?? null;
        if ($area !== null) {
            if ($search->area_min !== null && $area < $search->area_min) {
                return false;
            }
            if ($search->area_max !== null && $area > $search->area_max) {
                return false;
            }
        }

        // ss.lv understands region names from its own URL, so a matched
        // region is already filtered by the page. city24.lv ignores the
        // address[text] parameter, so apply the location locally there.
        if (filled($search->location)) {
            $needsLocalFilter = $portal === 'city24'
                || $this->ss->regionSlug($search->location) === null;

            if ($needsLocalFilter && ! $this->contains($item, (string) $search->location)) {
                return false;
            }
        }

        if (filled($search->keywords)) {
            $words = array_filter(array_map('trim', preg_split('/[,;]+/u', (string) $search->keywords)));
            if ($words !== []) {
                $haystack = $this->normalize(($item['title'] ?? '').' '.($item['address'] ?? '').' '.($item['location'] ?? ''));
                $hit = false;
                foreach ($words as $word) {
                    if ($word !== '' && str_contains($haystack, $this->normalize($word))) {
                        $hit = true;
                        break;
                    }
                }
                if (! $hit) {
                    return false;
                }
            }
        }

        return true;
    }

    private function contains(array $item, string $needle): bool
    {
        $haystack = $this->normalize(($item['address'] ?? '').' '.($item['location'] ?? ''));

        return str_contains($haystack, $this->normalize($needle));
    }

    private function fingerprint(array $item): ?string
    {
        $address = $this->normalize((string) ($item['address'] ?? ''));
        if ($address === '') {
            return null;
        }

        $area = (int) round((float) ($item['area_m2'] ?? 0));
        $price = (int) round(((float) ($item['price_eur'] ?? 0)) / 1000);

        return sha1($address.'|'.$area.'|'.$price);
    }

    private function findDuplicate(PropertySearch $search, string $fingerprint, string $portal): ?int
    {
        return ScrapedListing::query()
            ->where('user_id', $search->user_id)
            ->where('portal', '!=', $portal)
            ->where('fingerprint', $fingerprint)
            ->value('id');
    }

    private function normalize(string $value): string
    {
        $value = Str::ascii(mb_strtolower($value));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $value));
    }
}
