<?php

/**
 * Load private credentials from /private/config.php (outside deploy folder)
 * Hostinger: /home/user/private/config.php (3 levels up from bootstrap/)
 * Local dev: ./private/config.php (1 level up from bootstrap/)
 */
$localPath = dirname(__DIR__).'/private/config.php';
$hostingerPath = dirname(__DIR__, 3).'/private/config.php';

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
