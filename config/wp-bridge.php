<?php

declare(strict_types=1);

return [
    'host' => env('APP_HOST', 'crm.pardodlaimigs.lv'),
    'public_host' => env('PUBLIC_SITE_HOST', env('APP_ENV') === 'production' ? 'pardodlaimigs.lv' : 'localhost'),
    'public_url' => env('PUBLIC_SITE_URL', env('APP_ENV') === 'production' ? 'https://pardodlaimigs.lv' : env('APP_URL', 'http://localhost:8000')),

    'wordpress' => [
        'site_url' => env('WP_SITE_URL', 'https://pardodlaimigs.lv'),
        'feed_prefix' => env('WP_FEED_PREFIX', 'wp-json/crm/v1'),
        'api_key' => env('WP_CRM_API_KEY'),
    ],

    'feed' => [
        'per_page' => 100,
        'timeout' => 15,
        'retries' => 3,
    ],

    'audit' => [
        'retention_days' => 730,
    ],
];
