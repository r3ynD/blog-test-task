<?php

declare(strict_types=1);

try {
    $pdo = require dirname(__DIR__) . '/config/database.php';
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            filename VARCHAR(255) NOT NULL PRIMARY KEY,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $applied = $pdo->query('SELECT filename FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files = glob(dirname(__DIR__) . '/database/migrations/*.sql');

    if ($files === false) {
        throw new RuntimeException('Cannot list migration files.');
    }

    sort($files, SORT_STRING);
    $record = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');

    foreach ($files as $file) {
        $filename = basename($file);

        if (in_array($filename, $applied, true)) {
            continue;
        }

        $sql = file_get_contents($file);

        if ($sql === false) {
            throw new RuntimeException('Cannot read migration: ' . $filename);
        }

        $pdo->exec($sql);
        $record->execute([$filename]);
        echo 'Applied ' . $filename . PHP_EOL;
    }

    echo 'Migrations complete.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
