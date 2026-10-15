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
