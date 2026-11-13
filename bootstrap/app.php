<?php

$basePath = require __DIR__ . '/config.php';

use Rk\Threadwell\Core\Application;
use Rk\Threadwell\Core\Config;
use Rk\Threadwell\Core\Router;

$router = new Router(Config::get('app.url'));

require $basePath . '/routes/web.php';

$app = new Application($basePath);

return [
    'app' => $app,
    'router' => $router,
];
