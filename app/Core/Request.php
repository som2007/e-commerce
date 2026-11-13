<?php

namespace Rk\Threadwell\Core;

class Request
{
    private array $params = [];

    private ?array $jsonCache = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = parse_url($uri, PHP_URL_PATH);

        return $path ?: '/';
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /** Route parameter, e.g. {slug}. */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        $json = $this->json();

        return $json[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST, $this->json());
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $_SERVER[$key] ?? null;
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('Accept'), 'application/json')
            || strtolower((string) $this->header('X-Requested-With')) === 'xmlhttprequest'
            || str_contains($this->uri(), '/api/');
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function json(): array
    {
        if ($this->jsonCache !== null) {
            return $this->jsonCache;
        }

        $content = file_get_contents('php://input');

        if ($content === false || trim($content) === '') {
            return $this->jsonCache = [];
        }

        $data = json_decode($content, true);

        return $this->jsonCache = is_array($data) ? $data : [];
    }
}
