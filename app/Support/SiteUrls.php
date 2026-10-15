<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Site\SiteContent;

/**
 * Locale-aware URLs for the public site views: paths stay in one place so a
 * view never has to decide between the default and the prefixed route.
 */
final class SiteUrls
{
    public static function path(string $locale, string $path = '/'): string
    {
        return app(SiteContent::class)->localizedPath($locale, $path);
    }

    public static function property(string $locale, string $slug): string
    {
        return self::path($locale, '/ipasums/'.$slug.'/');
    }

    public static function post(string $locale, string $slug): string
    {
        return self::path($locale, '/'.$slug.'/');
    }

    public static function agent(string $locale, string $slug): string
    {
        return self::path($locale, '/agent/'.$slug.'/');
    }

    /** Locale switcher target for the page the visitor is on. */
    public static function languageUrl(string $locale, string $path): string
    {
        return app(SiteContent::class)->canonicalUrl($locale, $path);
    }
}
