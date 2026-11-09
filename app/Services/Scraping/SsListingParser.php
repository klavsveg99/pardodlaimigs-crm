<?php

declare(strict_types=1);

namespace App\Services\Scraping;

use App\Models\PropertySearch;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Reads ss.lv category listing pages and turns the table rows into plain
 * arrays. ss.lv has no public search API, so this uses the same HTML the
 * browser gets. Only the first two pages are read per run; older ads are
 * already stored and deduplicated by listing id.
 */
class SsListingParser
{
    private const CATEGORY_PATHS = [
        'land' => 'plots-and-lands',
        'houses' => 'homes-summer-residences',
        'apartments' => 'flats',
        'commercial' => 'premises',
    ];

    /** Location text -> ss.lv region slug (the slug appears in the page URL). */
    private const REGIONS = [
        'riga' => 'riga',
        'jurmala' => 'jurmala',
        'rigasrajons' => 'riga-region',
        'rigaregion' => 'riga-region',
        'aizkraukle' => 'aizkraukle-and-reg',
        'aluksne' => 'aluksne-and-reg',
        'balvi' => 'balvi-and-reg',
        'bauska' => 'bauska-and-reg',
        'cesis' => 'cesis-and-reg',
        'daugavpils' => 'daugavpils-and-reg',
        'dobele' => 'dobele-and-reg',
        'gulbene' => 'gulbene-and-reg',
        'jekabpils' => 'jekabpils-and-reg',
        'jelgava' => 'jelgava-and-reg',
        'kraslava' => 'kraslava-and-reg',
        'kuldiga' => 'kuldiga-and-reg',
        'liepaja' => 'liepaja-and-reg',
        'limbazi' => 'limbadzi-and-reg',
        'ludza' => 'ludza-and-reg',
        'madona' => 'madona-and-reg',
        'ogre' => 'ogre-and-reg',
        'preili' => 'preili-and-reg',
        'rezekne' => 'rezekne-and-reg',
        'saldus' => 'saldus-and-reg',
        'talsi' => 'talsi-and-reg',
        'tukums' => 'tukums-and-reg',
        'valka' => 'valka-and-reg',
        'valmiera' => 'valmiera-and-reg',
        'ventspils' => 'ventspils-and-reg',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetch(PropertySearch $search): array
    {
        $path = self::CATEGORY_PATHS[$search->category] ?? 'plots-and-lands';
        $base = 'https://www.ss.lv/lv/real-estate/'.$path.'/';
        $region = $this->regionSlug($search->location);
        if ($region !== null) {
            $base .= $region.'/';
        }

        $items = [];
        for ($page = 1; $page <= 2; $page++) {
            $url = $page === 1 ? $base : $base.'page'.$page.'.html';
            $html = $this->get($url);
            if ($html === null) {
                break;
            }

            $pageItems = $this->parse($html, $search->category);
            $items = array_merge($items, $pageItems);

            // A short page means there is nothing after it.
            if (count($pageItems) < 20) {
                break;
            }

            usleep(800_000);
        }

        return $items;
    }

    public function regionSlug(?string $location): ?string
    {
        if ($location === null || trim($location) === '') {
            return null;
        }

        $key = preg_replace('/[^a-z]/', '', $this->normalize($location));

        return self::REGIONS[$key] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parse(string $html, string $category): array
    {
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query('//tr[starts-with(@id, "tr_")]');
        if ($rows === false) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $id = substr($row->getAttribute('id'), 3);
            if ($id === '') {
                continue;
            }

            $link = $xpath->query('.//a[starts-with(@id, "dm_")]', $row)->item(0)
                ?: $xpath->query('.//a[contains(@href, "/msg/")]', $row)->item(0);
            if (! $link instanceof \DOMElement) {
                continue;
            }

            $href = $link->getAttribute('href');
            if ($href === '') {
                continue;
            }

            $cells = [];
            foreach ($xpath->query('.//td', $row) as $cell) {
                $cells[] = $this->clean($dom->saveHTML($cell));
            }

            if ($this->isRent($cells)) {
                continue;
            }

            $title = $this->clean($dom->saveHTML($link));
            $address = $cells[3] ?? '';
            [$area, $land] = $this->areas($cells, $category);

            $image = null;
            $img = $xpath->query('.//img', $row)->item(0);
            if ($img instanceof \DOMElement) {
                $image = $img->getAttribute('src');
                if (str_starts_with($image, '//')) {
                    $image = 'https:'.$image;
                }
            }

            $items[] = [
                'external_id' => $id,
                'url' => str_starts_with($href, 'http') ? $href : 'https://www.ss.lv'.$href,
                'title' => $title,
                'price_eur' => $this->price($cells),
                'area_m2' => $area,
                'land_m2' => $land,
                'location' => $address,
                'address' => $address,
                'image_url' => $image,
                'listed_at' => null,
            ];
        }

        return $items;
    }

    private function get(string $url): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; PardodLaimigsCRM/1.0; +https://pardodlaimigs.lv)',
            'Accept-Language' => 'lv,en;q=0.8',
        ])->timeout(20)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('ss.lv atbildēja ar '.$response->status());
        }

        return $response->body();
    }

    private function clean(string $value): string
    {
        $value = (string) preg_replace('/<br\s*\/?>/i', ' ', $value);
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * ss.lv mixes a few rental ads into the sale categories. A monthly price
     * is the reliable signal; "īres nams" in a sale title must not be skipped.
     *
     * @param  array<int, string>  $cells
     */
    private function isRent(array $cells): bool
    {
        foreach (array_slice($cells, 3) as $cell) {
            if (str_contains(mb_strtolower($cell), 'mēn')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $cells
     * @return array{0: ?int, 1: ?int}
     */
    private function areas(array $cells, string $category): array
    {
        $area = null;
        $land = null;

        foreach (array_slice($cells, 3) as $cell) {
            if (preg_match('/([\d\s\.,]+)\s*m²/iu', $cell, $match)) {
                $value = (int) preg_replace('/\D/', '', $match[1]);
                if ($value > 0) {
                    if ($land === null) {
                        $land = $value;
                    } elseif ($area === null) {
                        $area = $value;
                    }
                }
            }
        }

        if ($category === 'apartments' && $area === null && isset($cells[5]) && ctype_digit($cells[5])) {
            $area = (int) $cells[5];
        }

        if ($category === 'houses' && $area === null && isset($cells[4]) && ctype_digit($cells[4])) {
            $area = (int) $cells[4];
        }

        if ($category === 'land') {
            $area = $land ?? $area;
            $land = $area;
        }

        return [$area, $land];
    }

    /**
     * @param  array<int, string>  $cells
     */
    private function price(array $cells): ?int
    {
        foreach (array_reverse($cells) as $cell) {
            if (! str_contains($cell, '€')) {
                continue;
            }

            // Rent ads are not relevant to the development-property search.
            if (str_contains(mb_strtolower($cell), 'mēn')) {
                return null;
            }

            $value = (int) preg_replace('/\D/', '', $cell);

            return $value > 0 ? $value : null;
        }

        return null;
    }

    private function normalize(string $value): string
    {
        return Str::ascii(mb_strtolower(trim($value)));
    }
}
