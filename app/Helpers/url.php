<?php

use Rk\Threadwell\Core\Config;
use Rk\Threadwell\Core\Csrf;
use Rk\Threadwell\Core\Database;
use Rk\Threadwell\Core\Session;

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $baseUrl = rtrim((string) Config::get('app.url'), '/');

        return $baseUrl . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $baseUrl = rtrim((string) Config::get('app.url'), '/');

        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    /** Rs. 1,199.00 style (as used on the reference store). */
    function money(float|int|string|null $amount, bool $decimals = true): string
    {
        return 'Rs. ' . number_format((float) $amount, $decimals ? 2 : 0);
    }
}

if (!function_exists('db')) {
    function db(): PDO
    {
        return Database::shared();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return (new Csrf())->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('session')) {
    function session(): Session
    {
        static $session = null;

        return $session ??= new Session();
    }
}

if (!function_exists('flash')) {
    /** flash('key', 'value') to set; flash('key') to read once. */
    function flash(string $key, mixed $value = null): mixed
    {
        $s = session();

        if (func_num_args() === 2) {
            $s->set('_flash_' . $key, $value);

            return null;
        }

        $v = $s->get('_flash_' . $key);
        $s->remove('_flash_' . $key);

        return $v;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $old = session()->get('_old', []);

        return $old[$key] ?? $default;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to, int $status = 302): never
    {
        if (!preg_match('#^https?://#', $to)) {
            $to = url($to);
        }

        header('Location: ' . $to, true, $status);
        exit;
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        $s = session();

        return $s->get('authenticated') ? (int) $s->get('user_id') : null;
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        static $user = false;

        if ($user === false) {
            $id = auth_id();
            $user = null;

            if ($id) {
                $st = db()->prepare(
                    'SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.status, r.name AS role
                     FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? LIMIT 1'
                );
                $st->execute([$id]);
                $user = $st->fetch() ?: null;
            }
        }

        return $user;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $u = auth_user();

        return $u && in_array($u['role'], ['admin', 'staff'], true) && $u['status'] === 'active';
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);

        return trim((string) $text, '-') ?: 'item';
    }
}

if (!function_exists('abort')) {
    function abort(int $code = 404): never
    {
        throw new \Rk\Threadwell\Core\HttpException($code);
    }
}

if (!function_exists('product_image')) {
    /**
     * Resolve a stored image path to a URL.
     * - "shirt:RRGGBB:view[:sleeve]" => generated SVG illustration
     * - "uploads/..." or "images/..." => file under public/assets
     * - absolute http(s) URL => as-is
     */
    function product_image(?string $path): string
    {
        if (!$path) {
            return url('img/shirt/cccccc/front.svg');
        }

        if (str_starts_with($path, 'shirt:')) {
            $parts = explode(':', $path);
            $hex = preg_replace('/[^0-9a-fA-F]/', '', $parts[1] ?? 'cccccc');
            $view = $parts[2] ?? 'front';
            $sleeve = $parts[3] ?? 'full';
            $pat = preg_replace('/[^a-z]/', '', $parts[4] ?? '');

            return url("img/shirt/{$hex}/{$view}.svg?s={$sleeve}" . ($pat !== '' ? "&p={$pat}" : ''));
        }

        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        return asset($path);
    }
}

if (!function_exists('discount_percent')) {
    function discount_percent(float $price, ?float $sale): int
    {
        if (!$sale || $sale >= $price || $price <= 0) {
            return 0;
        }

        return (int) round((($price - $sale) / $price) * 100);
    }
}

if (!function_exists('time_ago')) {
    function time_ago(?string $date): string
    {
        return $date ? date('d M Y', strtotime($date)) : '';
    }
}

if (!function_exists('partial')) {
    function partial(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require dirname(__DIR__) . '/Views/partials/' . $name . '.php';
    }
}

if (!function_exists('stars')) {
    /** Rating as ★★★★☆ (rounded to nearest whole star). */
    function stars(float $avg): string
    {
        $full = (int) round($avg);

        return str_repeat('★', $full) . str_repeat('☆', max(0, 5 - $full));
    }
}

if (!function_exists('cart_count')) {
    function cart_count(): int
    {
        return array_sum((array) session()->get('cart', []));
    }
}

if (!function_exists('page_url')) {
    /** Build a query string keeping current filters. */
    function page_url(string $base, array $query, array $override = []): string
    {
        $q = array_filter(array_merge($query, $override), fn ($v) => $v !== null && $v !== '');

        return url($base) . ($q ? '?' . http_build_query($q) : '');
    }
}

if (!function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return \Rk\Threadwell\Core\Security::nonce();
    }
}

if (!function_exists('html_clean')) {
    function html_clean(string $html): string
    {
        return \Rk\Threadwell\Core\Security::cleanHtml($html);
    }
}

if (!function_exists('same_host_url')) {
    /** True only for URLs on this site (blocks open redirects). */
    function same_host_url(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        $self = parse_url((string) config('app.url'), PHP_URL_HOST);

        return $host === null ? str_starts_with($url, '/') && !str_starts_with($url, '//') : strcasecmp((string) $host, (string) $self) === 0;
    }
}
