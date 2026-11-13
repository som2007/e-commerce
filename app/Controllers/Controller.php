<?php

namespace Rk\Threadwell\Controllers;

use Rk\Threadwell\Core\Request;
use Rk\Threadwell\Core\View;
use Rk\Threadwell\Models\Catalog;
use Rk\Threadwell\Services\CartService;

abstract class Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        return (new View())->render($view, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    protected function catalog(): Catalog
    {
        return new Catalog(db());
    }

    protected function cart(): CartService
    {
        return new CartService(db());
    }

    /** Remember submitted form values for old(). */
    protected function remember(array $data): void
    {
        unset($data['password'], $data['_token']);
        session()->set('_old', $data);
    }

    protected function forget(): void
    {
        session()->remove('_old');
    }

    protected function str(Request $r, string $key, int $max = 255): string
    {
        $v = trim((string) $r->input($key, ''));

        return mb_substr($v, 0, $max);
    }

    protected function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        redirect($ref !== '' && same_host_url($ref) ? $ref : $fallback);
    }
}
