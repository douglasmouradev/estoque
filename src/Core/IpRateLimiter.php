<?php

declare(strict_types=1);

namespace App\Core;

/** Rate limit por IP em arquivo (portal, reset senha, API pública). */
final class IpRateLimiter
{
    public static function tooMany(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
        $dir = dirname(__DIR__, 2) . '/storage/rate_limit';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file = $dir . '/' . hash('sha256', $key . '|' . $ip) . '.json';
        $now = time();
        /** @var list<int> $attempts */
        $attempts = [];
        if (is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                $attempts = array_values(array_filter($data, static fn ($t): bool => is_int($t) && $t > $now - $windowSeconds));
            }
        }
        if (count($attempts) >= $maxAttempts) {
            return true;
        }
        $attempts[] = $now;
        file_put_contents($file, json_encode($attempts));
        return false;
    }
}
