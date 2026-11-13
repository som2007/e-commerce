<?php

namespace Rk\Threadwell\Core;

/**
 * Central security layer: headers + CSP, request guard (rate limits), logging, HTML sanitising.
 */
class Security
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || str_starts_with((string) Config::get('app.url'), 'https://');
    }

    /* ------------------------------------------------------------ headers */

    public static function headers(Request $request): void
    {
        if (headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');

        $uri = $request->uri();
        $script = "'self' 'nonce-" . self::nonce() . "' https://checkout.razorpay.com";
        $style = "'self' 'unsafe-inline' https://fonts.googleapis.com";

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$script}",
            "style-src {$style}",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https://nominatim.openstreetmap.org https://api.razorpay.com https://lumberjack.razorpay.com",
            "frame-src https://api.razorpay.com https://checkout.razorpay.com",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
        ]);

        header('Content-Security-Policy: ' . $csp);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=(self), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');

        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Never let browsers/proxies cache private or transactional pages.
        if (preg_match('#/(account|admin|checkout|payment|order|cart|login|register|api)(/|$)#', $uri)) {
            header('Cache-Control: no-store, no-cache, must-revalidate, private');
            header('Pragma: no-cache');
        }
    }

    /* ------------------------------------------------------------- guard */

    /**
     * Rate limit rules. [bucket, maxHits, windowSeconds]
     * Returns normally when allowed, otherwise sends 429 and exits.
     */
    public static function guard(Request $request): void
    {
        RateLimiter::gc();

        $ip = $request->ip();
        $method = $request->method();
        $uri = $request->uri();
        $base = rtrim((string) parse_url((string) Config::get('app.url'), PHP_URL_PATH), '/');
        $path = $base !== '' && str_starts_with($uri, $base) ? substr($uri, strlen($base)) : $uri;
        $path = '/' . trim($path, '/');

        // Generated images are cacheable and many per page: own generous bucket,
        // so they never eat into the limit for real page / API requests.
        $rules = str_starts_with($path, '/img/')
            ? [['img', 1500, 60]]
            : [['global', 600, 60]];

        if ($method === 'POST' && str_starts_with($path, '/api/v1/auth/')) {
            $rules[] = ['auth', 10, 60];
            $rules[] = ['auth-slow', 40, 3600];

            // Per-account throttle: stops password / OTP guessing from many IPs
            $email = strtolower(trim((string) ($request->json()['email'] ?? '')));
            if ($email !== '') {
                $rules[] = ['auth-acct:' . $email, 8, 900];
            }
        } elseif ($method === 'POST' && ($path === '/checkout' || str_starts_with($path, '/payment/'))) {
            $rules[] = ['checkout', 12, 600];
        } elseif ($method === 'POST' && in_array($path, ['/pages/contact', '/api/v1/newsletter', '/exchange-request'], true)) {
            $rules[] = ['forms', 6, 600];
        } elseif ($method === 'POST' && preg_match('#^/products/[^/]+/reviews$#', $path)) {
            $rules[] = ['reviews', 5, 900];
        } elseif ($path === '/track-order' && ($_GET['order'] ?? '') !== '') {
            $rules[] = ['track', 15, 600];
        } elseif ($path === '/api/v1/search/suggest') {
            $rules[] = ['suggest', 90, 60];
        } elseif ($path === '/search') {
            $rules[] = ['search', 60, 60];
        } elseif ($method === 'POST' && $path === '/api/v1/checkout/otp/send') {
            $rules[] = ['otp-send', 6, 600];
        } elseif ($method === 'POST' && $path === '/api/v1/checkout/otp/verify') {
            $rules[] = ['otp-verify', 20, 600];
        } elseif ($path === '/api/v1/checkout/quote') {
            $rules[] = ['quote', 30, 60];
        } elseif (str_starts_with($path, '/api/v1/cart')) {
            $rules[] = ['cart', 120, 60];
        } elseif (str_starts_with($path, '/admin') && $method === 'POST') {
            $rules[] = ['admin-write', 120, 60];
        }

        foreach ($rules as [$bucket, $max, $window]) {
            [$ok, $retry] = RateLimiter::hit($bucket . '|' . ($bucket === 'global' || !str_contains($bucket, ':') ? $ip : ''), $max, $window);

            if (!$ok) {
                self::log('rate_limited', ['bucket' => explode(':', $bucket)[0], 'path' => $path], $ip);
                self::tooMany($request, $retry);
            }
        }
    }

    private static function tooMany(Request $request, int $retry): never
    {
        http_response_code(429);
        header('Retry-After: ' . $retry);

        if ($request->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$retry} seconds.",
            ]);
        } else {
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
                . '<title>Too many requests</title><body style="font-family:system-ui;text-align:center;padding:12vh 20px">'
                . '<h1>Slow down a little</h1><p>Too many requests from your connection. Please try again in '
                . (int) $retry . ' seconds.</p></body>';
        }

        exit;
    }

    /* ----------------------------------------------------------- logging */

    public static function log(string $event, array $context = [], ?string $ip = null): void
    {
        $dir = Config::get('app.base_path') . '/storage/logs';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = json_encode([
            'time' => date('c'),
            'event' => $event,
            'ip' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
            'user' => function_exists('auth_id') ? auth_id() : null,
            'ctx' => $context,
        ], JSON_UNESCAPED_SLASHES);

        @file_put_contents($dir . '/security.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /* --------------------------------------------------------- sanitiser */

    private const ALLOWED = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'a', 'span',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'div',
    ];

    /** Allow-list HTML sanitiser for admin-edited CMS content (no scripts, handlers or javascript: URLs). */
    public static function cleanHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('root');

        if (!$root) {
            return e($html);
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function walk(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if (!$child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (!in_array($tag, self::ALLOWED, true)) {
                // Drop dangerous containers entirely, keep text of harmless unknown tags.
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math', 'link', 'meta', 'base', 'noscript', 'template'], true)) {
                    $node->removeChild($child);
                } else {
                    self::walk($child);
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                }
                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                $keep = ($tag === 'a' && in_array($name, ['href', 'title'], true))
                    || ($name === 'class' && preg_match('/^[\w\- ]+$/', $attr->value));

                if (!$keep) {
                    $child->removeAttribute($attr->name);
                }
            }

            if ($tag === 'a') {
                $href = trim(preg_replace('/[\x00-\x20]+/', '', $child->getAttribute('href')));

                if ($href !== '' && !preg_match('#^(https?://|mailto:|/|\#)#i', $href)) {
                    $child->removeAttribute('href');
                }

                if (preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('rel', 'noopener noreferrer nofollow');
                    $child->setAttribute('target', '_blank');
                }
            }

            self::walk($child);
        }
    }
}
