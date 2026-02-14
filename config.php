<?php

declare(strict_types=1);

// Basic app config. Override via environment variables in production.
$dbUrl = getenv('DB_URL') ?: '';
$dbConfig = [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'pijp',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
];

if ($dbUrl !== '') {
    $parts = parse_url($dbUrl);
    if ($parts !== false) {
        $dbConfig['host'] = $parts['host'] ?? $dbConfig['host'];
        $dbConfig['port'] = (string) ($parts['port'] ?? $dbConfig['port']);
        $dbConfig['name'] = ltrim($parts['path'] ?? '', '/') ?: $dbConfig['name'];
        $dbConfig['user'] = $parts['user'] ?? $dbConfig['user'];
        $dbConfig['pass'] = $parts['pass'] ?? $dbConfig['pass'];
    }
}

return [
    'app_name' => getenv('APP_NAME') ?: 'PIJP Gas Ordering',
    'base_url' => getenv('APP_BASE_URL') ?: '/',
    'db' => $dbConfig,
];
