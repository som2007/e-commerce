<?php

namespace Rk\Threadwell\Core;

use RuntimeException;

class Response
{
    public function send(
        string $content,
        int $status = 200
    ): void {
        http_response_code($status);
        echo $content;
    }

    public function json(
        mixed $data,
        int $status = 200
    ): void {
        http_response_code($status);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_THROW_ON_ERROR
        );
    }

    public function redirect(
        string $url,
        int $status = 302
    ): void {
        if (headers_sent()) {
            throw new RuntimeException(
                'Cannot redirect because output has already started.'
            );
        }

        header('Location: ' . $url, true, $status);
        exit;
    }

    public function view(
        string $view,
        array $data = [],
        int $status = 200
    ): void {
        if (
            $view === '' ||
            str_contains($view, '..') ||
            !preg_match('/^[a-zA-Z0-9_\/-]+$/', $view)
        ) {
            throw new RuntimeException('Invalid view name.');
        }

        $viewsDirectory = dirname(__DIR__) . '/Views';
        $realViewsDirectory = realpath($viewsDirectory);
        $realViewPath = realpath(
            $viewsDirectory . '/' . $view . '.php'
        );

        if (
            $realViewsDirectory === false ||
            $realViewPath === false ||
            !str_starts_with(
                $realViewPath,
                $realViewsDirectory . DIRECTORY_SEPARATOR
            ) ||
            !is_file($realViewPath)
        ) {
            throw new RuntimeException(
                "View not found: {$view}"
            );
        }

        http_response_code($status);
        extract($data, EXTR_SKIP);

        require $realViewPath;
    }
}
