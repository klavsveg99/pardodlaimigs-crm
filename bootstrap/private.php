<?php

/**
 * Load private credentials from outside the deploy folder.
 * Local dev: ./private/config.php
 * Hostinger: ~/private/config.php (home dir, outside public_html)
 */
// Always load private config (preferred over .env for credentials)
$localPath = dirname(__DIR__).'/private/config.php';
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
