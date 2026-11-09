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
     * @return array<int, array<string, mixed>>
     */
    public function fetch(PropertySearch $search): array
    {
        $unit = self::UNITS[$search->category] ?? 'Land';
        $items = [];

        for ($page = 1; $page <= 2; $page++) {
            $params = [
                'address' => ['cc' => 2],
                'tsType' => 'sale',
                'unitType' => $unit,
                'adReach' => 1,
                'itemsPerPage' => 50,
                'page' => $page,
            ];

            if (filled($search->location)) {
                $params['address']['text'] = $search->location;
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

            foreach ($data as $row) {
                if (is_array($row)) {
                    $items[] = $this->map($row, $search->category);
                }
            }

            if (count($data) < 50) {
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
            'image_url' => is_string($image) ? $image : null,
            'listed_at' => $listedAt,
        ];
    }
}
