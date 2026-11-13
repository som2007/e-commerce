<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use Rk\Threadwell\Core\Config;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));

$dotenv->load();

Config::load([
    'app' => [
        'name' => $_ENV['APP_NAME'] ?? 'Threadwell',
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var(
            $_ENV['APP_DEBUG'] ?? false,
            FILTER_VALIDATE_BOOL
        ),
        'url' => $_ENV['APP_URL'] ?? '',
    ],

    'database' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_DATABASE'] ?? '',
        'username' => $_ENV['DB_USERNAME'] ?? '',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
    ],
]);

echo Config::get('database.host');