<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\Models\PropertySearch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Reads city24.lv through the public search endpoint its own frontend uses.
 * The endpoint returns JSON, so no browser is needed.
 */
class City24ListingParser
{
    /** The API accepts large pages, so one request covers many listings. */
    private const PER_PAGE = 500;

    private const UNITS = [
        'land' => 'Land',
        'houses' => 'House',
        'apartments' => 'Apartment',
        'commercial' => 'Commercial',
    ];

    private const SLUGS = [
        'land' => 'land-lots-for-sale',
        'houses' => 'houses-for-sale',
        'apartments' => 'apartments-for-sale',
        'commercial' => 'commercials-for-sale',
    ];

    /**
     * @param  array<string, true>  $knownIds  Ads already stored for this agent and portal.
     * @param  (callable(int): void)|null  $onPage  Called after each listing page is parsed.
     * @return array<int, array<string, mixed>>
     */
    public function fetch(PropertySearch $search, array $knownIds = [], ?callable $onPage = null): array
    {
        $unit = self::UNITS[$search->category] ?? 'Land';
        $seen = $knownIds;
        $items = [];

        for ($page = 1; ; $page++) {
            $params = [
                'address' => ['cc' => 2],
                'tsType' => 'sale',
                'unitType' => $unit,
                'adReach' => 1,
                'itemsPerPage' => self::PER_PAGE,
                'page' => $page,
            ];

            if (filled($search->location)) {
                $location = $this->resolveLocation((string) $search->location);

                if ($location !== null) {
                    // city24 ignores address[text]; its own location id is
                    // what actually narrows the result set before we filter
                    // the remaining criteria locally.
                    $params['address'][$location['type']] = $location['id'];
                } else {
                    $params['address']['text'] = $search->location;
                }
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (compatible; PardodLaimigsCRM/1.0; +https://pardodlaimigs.lv)',
                'Referer' => 'https://www.city24.lv/',
            ])->timeout(20)->get('https://api.city24.lv/lv_LV/search/realties', $params);

            if (! $response->successful()) {
                throw new \RuntimeException('city24.lv atbildēja ar '.$response->status());
            }

            $data = $response->json();
            if (! is_array($data) || $data === []) {
                break;
            }

            if ($onPage !== null) {
                $onPage($page);
            }

            $fresh = 0;
            foreach ($data as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $item = $this->map($row, $search->category);
                $items[] = $item;

                $id = (string) ($item['external_id'] ?? '');
                if ($id !== '' && ! isset($seen[$id])) {
                    $seen[$id] = true;
                    $fresh++;
                }
            }

            // Pages are newest first: once a page has nothing new, older pages
            // cannot bring new ads either. A short page is the last page.
            if ($fresh === 0 || count($data) < self::PER_PAGE) {
                break;
            }

            usleep(800_000);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function map(array $row, string $category): array
    {
        $friendlyId = (string) ($row['friendly_id'] ?? $row['id'] ?? '');
        $slug = self::SLUGS[$category] ?? 'land-lots-for-sale';

        $address = $row['address'] ?? [];
        $street = (string) ($address['street']['name'] ?? '');
        $house = (string) ($address['house_number'] ?? '');
        $addressLine = trim($street.($house !== '' ? ' '.$house : ''));

        $location = (string) (
            $address['city_name']
            ?? $address['county_name']
            ?? $address['parish_name']
            ?? ($address['village']['name'] ?? '')
        );

        $title = (string) ($row['slogans']['lv_LV']['slogan'] ?? '');
        if ($title === '') {
            $title = trim((string) ($row['unit_type'] ?? '').' '.$addressLine.' '.$location);
        }

        $image = $row['main_image']['url'] ?? ($row['images'][0] ?? null);
        if (is_string($image)) {
            $image = str_replace('{fmt:em}', '13', $image);
        }

        $size = isset($row['property_size']) && is_numeric($row['property_size'])
            ? (int) round((float) $row['property_size'])
            : null;
        $lot = isset($row['lot_size']) && is_numeric($row['lot_size'])
            ? (int) round((float) $row['lot_size'])
            : null;

        $area = $size;
        $land = $lot;

        if ($category === 'land') {
            $area = $lot ?? $size;
            $land = $area;
        }

        $contactEmail = $this->contactEmail($row);

        $listedAt = null;
        if (! empty($row['date_published'])) {
            try {
                $listedAt = Carbon::parse((string) $row['date_published']);
            } catch (\Throwable) {
                $listedAt = null;
            }
        }

        return [
            'external_id' => $friendlyId,
            'url' => 'https://www.city24.lv/real-estate/'.$slug.'/'.$friendlyId,
            'title' => $title,
            'price_eur' => isset($row['price']) && is_numeric($row['price']) ? (int) round((float) $row['price']) : null,
            'area_m2' => $area,
            'land_m2' => $land,
            'location' => $location,
            'address' => $addressLine,
            'contact_email' => $contactEmail,
            'image_url' => is_string($image) ? $image : null,
            'listed_at' => $listedAt,
        ];
    }

    /**
     * Resolve a free-text location to the city24 address filter. Without this
     * the API returns apartments for the whole country and the runner has to
     * download every page before applying the local location filter.
     *
     * @return array{type: string, id: int}|null
     */
    private function resolveLocation(string $location): ?array
    {
        static $cache = [];

        $key = mb_strtolower(trim($location));

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'Mozilla/5.0 (compatible; PardodLaimigsCRM/1.0; +https://pardodlaimigs.lv)',
                'Referer' => 'https://www.city24.lv/',
            ])->timeout(10)->get('https://api.city24.lv/lv_LV/search/autocomplete', [
                'q' => $location,
            ]);

            if (! $response->successful()) {
                return $cache[$key] = null;
            }

            foreach ((array) $response->json() as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $content = $row['content'] ?? [];

                foreach (['county', 'city', 'parish'] as $type) {
                    $id = $content[$type]['id'] ?? null;

                    if (is_numeric($id) && (int) $id > 0) {
                        return $cache[$key] = ['type' => $type, 'id' => (int) $id];
                    }
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $cache[$key] = null;
    }

    /**
     * city24 exposes the broker's e-mail in the search result, which is what
     * automatic offers are sent to. Prefer the ad's own contact, then the
     * office contact.
     *
     * @param  array<string, mixed>  $row
     */
    private function contactEmail(array $row): ?string
    {
        $candidates = [];

        foreach ((array) ($row['broker']['business_card']['additional_contacts'] ?? []) as $contact) {
            if (is_array($contact) && strtoupper((string) ($contact['dtype'] ?? '')) === 'EMAIL') {
                $candidates[] = $contact['value'] ?? null;
            }
        }

        foreach ((array) ($row['broker']['office'] ?? []) as $office) {
            foreach ((array) ($office['business_card']['email_addresses'] ?? []) as $email) {
                $candidates[] = $email;
            }
        }

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return null;
    }
}
