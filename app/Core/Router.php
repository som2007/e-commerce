<?php

namespace Rk\Threadwell\Core;

class Router
{
    private array $routes = [];

    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = $this->normalizeBasePath($basePath);
    }

    public function get(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->add('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->add('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->add('PUT', $uri, $action, $middleware);
    }

    public function patch(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->add('PATCH', $uri, $action, $middleware);
    }

    public function delete(string $uri, callable|array $action, array $middleware = []): void
    {
        $this->add('DELETE', $uri, $action, $middleware);
    }

    /**
     * Group routes under a shared prefix + middleware.
     */
    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $callback(new class($this, $prefix, $middleware) {
            public function __construct(
                private Router $router,
                private string $prefix,
                private array $middleware
            ) {
            }

            public function __call(string $method, array $args): void
            {
                [$uri, $action] = $args;
                $extra = $args[2] ?? [];

                $this->router->{$method}(
                    rtrim($this->prefix, '/') . '/' . ltrim($uri, '/'),
                    $action,
                    array_merge($this->middleware, $extra)
                );
            }
        });
    }

    private function add(string $method, string $uri, callable|array $action, array $middleware = []): void
    {
        $uri = $this->normalizeUri($uri);

        $pattern = preg_replace(
            '#\{(\w+)\}#',
            '(?P<$1>[^/]+)',
            $uri
        );

        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'pattern' => '#^' . $pattern . '$#',
            'action' => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): mixed
    {
        $requestMethod = $request->method();

        // HTML forms can spoof PUT/PATCH/DELETE through _method.
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $spoof = strtoupper((string) $_POST['_method']);
            if (in_array($spoof, ['PUT', 'PATCH', 'DELETE'], true)) {
                $requestMethod = $spoof;
            }
        }

        $requestUri = $this->normalizeUri(
            $this->removeBasePath($request->uri())
        );

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            if (!preg_match($route['pattern'], $requestUri, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = urldecode($value);
                }
            }

            $request->setParams($params);

            foreach ($route['middleware'] as $middleware) {
                if ((new $middleware())->handle($request) === false) {
                    return null;
                }
            }

            return $this->execute($route['action'], $request);
        }

        return $this->notFound($request);
    }

    private function notFound(Request $request): mixed
    {
        http_response_code(404);

        if ($request->wantsJson()) {
            header('Content-Type: application/json');

            return json_encode([
                'success' => false,
                'message' => 'Not found.',
            ]);
        }

        if (function_exists('is_admin') && is_admin() && preg_match('#/admin(/|$)#', $request->uri())) {
            return (new View())->render('errors/admin-404', ['title' => 'Page not found'], 'admin/layout');
        }

        return (new View())->render('errors/404', [
            'title' => 'Page not found',
        ]);
    }

    private function execute(callable|array $action, Request $request): mixed
    {
        if (is_callable($action)) {
            return $action($request);
        }

        [$controller, $method] = $action;

        return (new $controller())->{$method}($request);
    }

    // private function removeBasePath(string $uri): string
    // {
    //     if (
    //         $this->basePath !== '' &&
    //         ($uri === $this->basePath || str_starts_with($uri, $this->basePath . '/'))
    //     ) {
    //         $uri = substr($uri, strlen($this->basePath));
    //     }

    //     return $uri ?: '/';
    // }

        private function removeBasePath(string $uri): string
    {
        // Strip the full base (/threadwell/public) and also the project folder
        // alone (/threadwell), because the root .htaccess serves public/ from it.
        $bases = [$this->basePath];
        if (str_ends_with($this->basePath, '/public')) {
            $bases[] = substr($this->basePath, 0, -strlen('/public'));
        }
        usort($bases, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($bases as $base) {
            if ($base !== '' && ($uri === $base || str_starts_with($uri, $base . '/'))) {
                $uri = substr($uri, strlen($base));
                break;
            }
        }

        return $uri ?: '/';
    }

    private function normalizeUri(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';

        $uri = '/' . trim($uri, '/');

        return $uri === '//' ? '/' : $uri;
    }

    private function normalizeBasePath(string $basePath): string
    {
        if ($basePath === '') {
            return '';
        }

        $path = parse_url($basePath, PHP_URL_PATH) ?: '';

        $path = '/' . trim($path, '/');

        return $path === '/' ? '' : $path;
    }
}
