<?php

/**
 * Shared bootstrap: autoload, env, config. Used by web, migrate and seed.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/url.php';

use Dotenv\Dotenv;
use Rk\Threadwell\Core\Config;

$basePath = dirname(__DIR__);

Dotenv::createImmutable($basePath)->safeLoad();

date_default_timezone_set('Asia/Kolkata');

$env = static function (string $key, mixed $default = '') {
    $real = getenv($key);

    if ($real !== false && $real !== '') {
        return $real;
    }

    return ($_ENV[$key] ?? '') !== '' ? $_ENV[$key] : $default;
};

Config::load([
    'app' => [
        'name' => $env('APP_NAME', 'Threadwell'),
        'env' => $env('APP_ENV', 'production'),
        'debug' => filter_var($env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
        'url' => $env('APP_URL', ''),
        'base_path' => $basePath,
    ],

    'database' => [
        'driver' => $env('DB_DRIVER', 'mysql'),
        'host' => $env('DB_HOST', '127.0.0.1'),
        'port' => $env('DB_PORT', '3306'),
        'database' => $env('DB_DATABASE', ''),
        'username' => $env('DB_USERNAME', ''),
        'password' => $env('DB_PASSWORD', ''),
        'sqlite_path' => $env('DB_SQLITE_PATH', $basePath . '/storage/database.sqlite'),
    ],

    'mail' => [
        'mailer' => $env('MAIL_MAILER', 'smtp'),
        'host' => $env('MAIL_HOST', 'smtp.gmail.com'),
        'port' => (int) $env('MAIL_PORT', 587),
        'username' => $env('MAIL_USERNAME', ''),
        'password' => $env('MAIL_PASSWORD', ''),
        'encryption' => $env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => $env('MAIL_FROM_ADDRESS', ''),
        'from_name' => $env('MAIL_FROM_NAME', $env('APP_NAME', 'Threadwell')),
    ],

    'store' => [
        'email' => $env('STORE_EMAIL', 'support@example.com'),
        'phone' => $env('STORE_PHONE', '+91 00000 00000'),
        'address' => $env('STORE_ADDRESS', 'Your registered business address, India'),
        'free_shipping_over' => (float) $env('STORE_FREE_SHIPPING_OVER', 999),
        'shipping_fee' => (float) $env('STORE_SHIPPING_FEE', 80),
        'cod_fee' => (float) $env('STORE_COD_FEE', 50),
        'prepaid_discount_percent' => (float) $env('STORE_PREPAID_DISCOUNT', 10),
        'tagline' => 'Trends fade, but style remains!',
    ],

    'payment' => [
        'razorpay_key' => $env('RAZORPAY_KEY_ID', ''),
        'razorpay_secret' => $env('RAZORPAY_KEY_SECRET', ''),
    ],
]);

return $basePath;
