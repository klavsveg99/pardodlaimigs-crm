<?php

declare(strict_types=1);

return [
    'host' => env('APP_HOST', 'crm.pardodlaimigs.lv'),

    'wordpress' => [
        'site_url'    => env('WP_SITE_URL', 'https://pardodlaimigs.lv'),
        'feed_prefix' => env('WP_FEED_PREFIX', 'wp-json/crm/v1'),
        'api_key'     => env('WP_CRM_API_KEY'),
    ],

    'feed' => [
        'per_page' => 100,
        'timeout' => 15,
        'retries' => 3,
        'reconcile_cron' => env('WP_RECONCILE_CRON', '*/5 * * * *'),
    ],

    'audit' => [
        'retention_days' => 730,
    ],
];
