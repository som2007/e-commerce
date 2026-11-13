<?php

namespace Rk\Threadwell\Core;

class Session
{
    private const IDLE_TIMEOUT = 7200;      // 2 hours of inactivity
    private const ABSOLUTE_TIMEOUT = 43200; // 12 hours max

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');

        session_name('fh_session');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => Security::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        $now = time();

        // Expire idle / very old sessions (also limits stolen-cookie lifetime)
        if (
            isset($_SESSION['_last'], $_SESSION['_born'])
            && ($now - $_SESSION['_last'] > self::IDLE_TIMEOUT || $now - $_SESSION['_born'] > self::ABSOLUTE_TIMEOUT)
        ) {
            $_SESSION = [];
            session_regenerate_id(true);
        }

        $_SESSION['_born'] ??= $now;
        $_SESSION['_last'] = $now;

        // Bind the session to the browser family: a cheap guard against cookie theft replay.
        $ua = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|fh');

        if (isset($_SESSION['_ua']) && !hash_equals($_SESSION['_ua'], $ua)) {
            Security::log('session_ua_mismatch');
            $_SESSION = [];
            session_regenerate_id(true);
        }

        $_SESSION['_ua'] = $ua;
    }

    public function regenerate(): void
    {
        $this->start();

        session_regenerate_id(true);
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();

        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->start();

        return array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        $this->start();

        unset($_SESSION[$key]);
    }

    public function destroy(): void
    {
        $this->start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }
}
