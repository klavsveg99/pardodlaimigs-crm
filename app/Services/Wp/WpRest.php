<?php

declare(strict_types=1);

namespace App\Services\Wp;

use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

/**
 * Client for the WordPress CRM feed (wp-json/crm/v1/properties and /wpforms).
 *
 * The feed is the single source of truth for property and form-submission
 * data. Requests are authenticated with the X-CRM-API-Key header and
 * paginated via the `pagination.total_pages` envelope returned by the
 * endpoint.
 */
class WpRest
{
    public function __construct(private readonly LoggerInterface $log) {}

    /**
     * @return iterable<int, object>
     */
    public function eachProperty(int $perPage = 100): iterable
    {
        return $this->paginate('/properties', [], $perPage, 'properties');
    }

    /**
     * Fetch WPForms entries, optionally only those created/updated after
     * the given ISO-8601 timestamp.
     *
     * @return iterable<int, object>
     */
    public function eachEntry(?string $since = null, int $perPage = 100): iterable
    {
        return $this->paginate('/wpforms', $since ? ['since' => $since] : [], $perPage, 'entries');
    }

    /**
     * @param  array<string, string>  $extraParams
     * @return iterable<int, object>
     */
    private function paginate(string $path, array $extraParams, int $perPage, string $rowsKey): iterable
    {
        $perPage = min(max($perPage, 1), 100);
        $url = $this->base().$path;

        for ($page = 1; ; $page++) {
            $response = Http::withHeaders($this->headers())
                ->timeout(config('wp-bridge.feed.timeout', 15))
                ->retry(config('wp-bridge.feed.retries', 3), 200)
                ->get($url, array_merge(['per_page' => $perPage, 'page' => $page], $extraParams));

            if ($response->failed()) {
                $this->log->error('WP feed request failed', [
                    'path' => $path,
                    'page' => $page,
                    'status' => $response->status(),
                ]);

                return;
            }

            $body = $response->json();
            if (! is_array($body)) {
                $this->log->error('WP feed returned invalid response', [
                    'path' => $path,
                    'page' => $page,
                    'body' => $body,
                ]);

                return;
            }

            // Handle new mu-plugin format (has 'data' key)
            if (isset($body['data'])) {
                $rows = $body['data'];
                $hasMore = $body['has_more'] ?? false;
            }
            // Handle old format (has 'success' key)
            elseif (isset($body['success'])) {
                if (! ($body['success'] ?? false)) {
                    $this->log->error('WP feed returned failure', [
                        'path' => $path,
                        'page' => $page,
                        'body' => $body,
                    ]);

                    return;
                }

                $rows = $body[$rowsKey] ?? [];
                $totalPages = (int) ($body['pagination']['total_pages'] ?? $page);
                $hasMore = $page < $totalPages;
            } else {
                $this->log->error('WP feed returned unknown format', [
                    'path' => $path,
                    'page' => $page,
                    'body' => $body,
                ]);

                return;
            }

            foreach ($rows as $row) {
                yield (object) $row;
            }

            if (! $hasMore || ! count($rows)) {
                return;
            }
        }
    }

    private function base(): string
    {
        return rtrim(config('wp-bridge.wordpress.site_url'), '/')
             .'/'.trim(config('wp-bridge.wordpress.feed_prefix'), '/');
    }

    private function headers(): array
    {
        return [
            'Accept'        => 'application/json',
            'X-CRM-API-Key' => (string) config('wp-bridge.wordpress.api_key'),
        ];
    }
}
