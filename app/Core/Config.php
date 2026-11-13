<?php

namespace Rk\Threadwell\Core;

class Config
{
    private static array $config = [];

    public static function load(array $config): void
    {
        self::$config = $config;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);

        $value = self::$config;

        foreach ($keys as $item) {
            if (!is_array($value) || !array_key_exists($item, $value)) {
                return $default;
            }

            $value = $value[$item];
        }

        return $value;
    }
}