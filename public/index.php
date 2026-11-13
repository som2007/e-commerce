<?php

// Let `php -S` serve real static files directly.
if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $file = __DIR__ . $path;

    if ($path !== '/' && is_file($file)) {
        return false;
    }
}

$bootstrap = require dirname(__DIR__) . '/bootstrap/app.php';

$router = $bootstrap['router'];

use Rk\Threadwell\Core\Config;
use Rk\Threadwell\Core\HttpException;
use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Core\Response;
use Rk\Threadwell\Core\View;

ini_set('display_errors', '0');

$request = new Request();
$response = new Response();

try {
    \Rk\Threadwell\Core\Security::headers($request);
    \Rk\Threadwell\Core\Security::guard($request);

    session()->start();

    $result = $router->dispatch($request);

    if ($result !== null) {
        if (is_string($result)) {
            $response->send($result, http_response_code() ?: 200);
        }
    }
} catch (HttpException $e) {
    // Forbidden pages look exactly like missing pages, so protected areas are not revealed.
    $status = $e->status() === 403 ? 404 : $e->status();
    http_response_code($status);

    if ($request->wantsJson()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $status === 404 ? 'Not found.' : ($e->getMessage() ?: 'Error ' . $status)]);
    } elseif (is_admin() && preg_match('#/admin(/|$)#', $request->uri())) {
        echo (new View())->render('errors/admin-404', ['title' => 'Page not found'], 'admin/layout');
    } else {
        echo (new View())->render($status === 404 ? 'errors/404' : 'errors/500', [
            'title' => $status === 404 ? 'Page not found' : 'Error',
            'code' => $status,
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);

    error_log((string) $e);

    if ($request->wantsJson()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => Config::get('app.debug') ? $e->getMessage() : 'Something went wrong.',
        ]);
    } elseif (Config::get('app.debug')) {
        echo '<pre style="padding:20px;white-space:pre-wrap">'
            . htmlspecialchars((string) $e) . '</pre>';
    } else {
        echo (new View())->render('errors/500', ['title' => 'Server error']);
    }
}
