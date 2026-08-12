<?php

/**
 * Load private credentials from /private/config.php (outside deploy folder)
 * Hostinger deletes /crm on every deploy, so secrets must live in /private
 */
$privateConfigPath = dirname(__DIR__, 2) . '/private/config.php';

if (is_file($privateConfigPath)) {
    $privateConfig = require $privateConfigPath;
    
    // Merge into $_ENV so Laravel's env() helper picks them up
    foreach ($privateConfig as $key => $value) {
        if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}