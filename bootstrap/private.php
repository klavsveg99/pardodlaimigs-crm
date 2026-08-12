<?php

/**
 * Load private credentials from outside the deploy folder.
 * Local dev: ./private/config.php
 * Hostinger: ~/private/config.php (home dir, outside public_html)
 */
if (! is_file(dirname(__DIR__, 2).'/.env') && ! is_file(dirname(__DIR__).'/.env')) {
    // Local dev path
    $localPath = dirname(__DIR__).'/private/config.php';
    // Hostinger: ~/domains/pardodlaimigs.lv/public_html/crm/bootstrap/private.php
    // dirname(__DIR__,5) = /home/u976787655/
    $hostingerPath = dirname(__DIR__, 5).'/private/config.php';

    $configPath = is_file($localPath) ? $localPath : ($hostingerPath ?: null);

    if ($configPath && is_file($configPath)) {
        $privateConfig = require $configPath;

        foreach ($privateConfig as $key => $value) {
            if (! array_key_exists($key, $_ENV) && ! array_key_exists($key, $_SERVER)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
