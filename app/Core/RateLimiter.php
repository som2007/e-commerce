<?php

namespace Rk\Threadwell\Core;

/**
 * Small file based fixed-window rate limiter (no Redis/DB needed).
 * Each key gets one file under storage/ratelimit; access is serialised with flock().
 */
class RateLimiter
{
    private static function dir(): string
    {
        $dir = Config::get('app.base_path') . '/storage/ratelimit';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /**
     * Register a hit. Returns [allowed, retryAfterSeconds, remaining].
     */
    public static function hit(string $key, int $max, int $windowSeconds): array
    {
        $file = self::dir() . '/' . hash('sha256', $key) . '.json';
        $now = time();

        $fh = @fopen($file, 'c+');

        if (!$fh) {
            // Never lock everybody out because the disk is read-only.
            return [true, 0, $max];
        }

        flock($fh, LOCK_EX);

        $raw = stream_get_contents($fh);
        $data = $raw ? json_decode($raw, true) : null;

        if (!is_array($data) || ($data['reset'] ?? 0) <= $now) {
            $data = ['count' => 0, 'reset' => $now + $windowSeconds];
        }

        $data['count']++;

        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, json_encode($data));
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);

        $allowed = $data['count'] <= $max;

        return [$allowed, $allowed ? 0 : max(1, $data['reset'] - $now), max(0, $max - $data['count'])];
    }

    /** Delete expired counters (called randomly, keeps the folder small). */
    public static function gc(): void
    {
        if (random_int(1, 200) !== 1) {
            return;
        }

        foreach (glob(self::dir() . '/*.json') ?: [] as $f) {
            if (filemtime($f) < time() - 7200) {
                @unlink($f);
            }
        }
    }
}
