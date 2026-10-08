<?php

declare(strict_types=1);

$config = [];

foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'] as $name) {
    $value = getenv($name);

    if ($value === false || $value === '') {
        throw new RuntimeException('Missing environment variable: ' . $name);
    }

    $config[$name] = $value;
}

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $config['DB_HOST'],
    $config['DB_PORT'],
    $config['DB_NAME']
);

return new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
