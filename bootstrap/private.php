<?php

/**
 * Load private credentials from /private/config.php (outside deploy folder)
 * Only used when .env is missing (production on Hostinger).
 * Local dev uses .env for environment overrides.
 */

if (!is_file(dirname(__DIR__, 2) . '/.env') && !is_file(dirname(__DIR__) . '/.env')) {
    $localPath = dirname(__DIR__) . '/private/config.php';
    $hostingerPath = dirname(__DIR__, 3) . '/private/config.php';

    $configPath = is_file($localPath) ? $localPath : $hostingerPath;

    if ($configPath && is_file($configPath)) {
        $privateConfig = require $configPath;

        foreach ($privateConfig as $key => $value) {
            if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
