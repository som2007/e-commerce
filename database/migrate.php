<?php

$basePath = require dirname(__DIR__) . '/bootstrap/config.php';

use Rk\Threadwell\Core\Database;
use Rk\Threadwell\Core\Migration;

if (Rk\Threadwell\Core\Config::get('database.driver') === 'sqlite') {
    @mkdir(dirname(Rk\Threadwell\Core\Config::get('database.sqlite_path')), 0775, true);
}

$db = (new Database())->connection();

echo "Database connection successful." . PHP_EOL;

$db->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        batch INT UNSIGNED NOT NULL,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB
    DEFAULT CHARSET=utf8mb4
    COLLATE=utf8mb4_unicode_ci
");

$files = glob($basePath . '/database/migrations/*.php');
sort($files);

$executed = $db->query('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
$batch = (int) $db->query('SELECT COALESCE(MAX(batch), 0) FROM migrations')->fetchColumn() + 1;

foreach ($files as $file) {
    $name = basename($file, '.php');

    if (in_array($name, $executed, true)) {
        continue;
    }

    $migration = (require $file)($db);

    if (!$migration instanceof Migration) {
        throw new RuntimeException("Invalid migration: {$name}");
    }

    $migration->up();

    $db->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)')
        ->execute([$name, $batch]);

    echo "Migrated: {$name}" . PHP_EOL;
}

echo "Migration process completed." . PHP_EOL;
