<?php

namespace Rk\Threadwell\Core;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function token(): string
    {
        $session = new Session();

        $token = $session->get(
            self::SESSION_KEY
        );

        if (!$token) {
            $token = bin2hex(
                random_bytes(32)
            );

            $session->set(
                self::SESSION_KEY,
                $token
            );
        }

        return $token;
    }

    public function validate(
        ?string $token
    ): bool {
        if (!$token) {
            return false;
        }

        $session = new Session();

        $storedToken = $session->get(
            self::SESSION_KEY
        );

        if (!$storedToken) {
            return false;
        }

        return hash_equals(
            $storedToken,
            $token
        );
    }
}