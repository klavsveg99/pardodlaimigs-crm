<?php

declare(strict_types=1);

namespace App\Services\Wp;

/**
 * Read-only access to the WordPress CRM feed.
 *
 * The JSON feed (wp-json/crm/v1/properties and /wpforms) is the source of
 * truth for property data and contact form submissions. Property `id` is the
 * external WordPress property ID; entry `external_id` is "form_id:entry_id".
 */
class WpSource
{
    public function __construct(private readonly WpRest $feed) {}

    /**
     * @return iterable<int, object>
     */
    public function eachProperty(int $perPage = 100): iterable
    {
        return $this->feed->eachProperty($perPage);
    }

    /**
     * @return iterable<int, object>
     */
    public function eachEntry(?string $since = null, int $perPage = 100): iterable
    {
        return $this->feed->eachEntry($since, $perPage);
    }
}
