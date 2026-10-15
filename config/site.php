<?php

declare(strict_types=1);

return [
    /*
     * The Laravel-rendered public site is finished but NOT live: while
     * pardodlaimigs.lv is still served by WordPress, everything below stays
     * dormant. PUBLIC_SITE_ENABLED=1 (and pointing the public host at this
     * app) is the switch that moves the site over.
     */
    'enabled' => (bool) env('PUBLIC_SITE_ENABLED', false),

    'preview_snapshot' => env('PUBLIC_SITE_PREVIEW_DATA', env('APP_ENV') !== 'production'),

    'preview_properties' => resource_path('site/properties-preview.json'),
    'content_file' => resource_path('site/content.json'),
    'translations_file' => resource_path('site/translations.json'),

    'locales' => ['lv', 'en', 'ru', 'de'],

    // Blog cards per batch: the homepage renders this many and the load more
    // button (site.blog.more) serves the next batch.
    'blog_batch' => 3,

    /*
     * Country dial codes and the national number length each one accepts, for
     * the valuation form's prefix selector and its validation. The static
     * preview validates against the same list (phonePrefixes in src/main.js),
     * so keep them in step.
     */
    'phone_prefixes' => [
        'LV' => ['dial' => '371', 'min' => 8, 'max' => 8],
        'AT' => ['dial' => '43', 'min' => 8, 'max' => 11],
        'BE' => ['dial' => '32', 'min' => 8, 'max' => 9],
        'BY' => ['dial' => '375', 'min' => 9, 'max' => 9],
        'CH' => ['dial' => '41', 'min' => 9, 'max' => 9],
        'CZ' => ['dial' => '420', 'min' => 9, 'max' => 9],
        'DE' => ['dial' => '49', 'min' => 6, 'max' => 11],
        'DK' => ['dial' => '45', 'min' => 8, 'max' => 8],
        'EE' => ['dial' => '372', 'min' => 7, 'max' => 8],
        'ES' => ['dial' => '34', 'min' => 9, 'max' => 9],
        'FI' => ['dial' => '358', 'min' => 6, 'max' => 12],
        'FR' => ['dial' => '33', 'min' => 9, 'max' => 9],
        'GB' => ['dial' => '44', 'min' => 9, 'max' => 10],
        'IE' => ['dial' => '353', 'min' => 7, 'max' => 9],
        'IT' => ['dial' => '39', 'min' => 8, 'max' => 11],
        'LT' => ['dial' => '370', 'min' => 8, 'max' => 8],
        'NL' => ['dial' => '31', 'min' => 9, 'max' => 9],
        'NO' => ['dial' => '47', 'min' => 8, 'max' => 8],
        'PL' => ['dial' => '48', 'min' => 9, 'max' => 9],
        'PT' => ['dial' => '351', 'min' => 9, 'max' => 9],
        'RU' => ['dial' => '7', 'min' => 10, 'max' => 10],
        'SE' => ['dial' => '46', 'min' => 7, 'max' => 10],
        'UA' => ['dial' => '380', 'min' => 9, 'max' => 9],
        'US' => ['dial' => '1', 'min' => 10, 'max' => 10],
    ],

    'default_phone_prefix' => 'LV',

    'language_names' => [
        'lv' => 'Latviešu',
        'en' => 'English',
        'ru' => 'Русский',
        'de' => 'Deutsch',
    ],

    'language_flags' => [
        'lv' => 'lv.png',
        'en' => 'en_GB.png',
        'ru' => 'ru_RU.png',
        'de' => 'de_DE.png',
    ],

    'property_categories' => [
        'Lauksaimniecības zeme',
        'Lauku īpašums',
        'Zeme',
        'Komercīpašums',
        'Māja',
        'Dzīvoklis',
        'Mežs',
        'Pirts',
        'Komerciāls',
        'Garāža',
    ],
];
