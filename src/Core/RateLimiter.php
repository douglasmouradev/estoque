<?php

declare(strict_types=1);

namespace App\Core;

/** Limite simples por sessão (login, etc.). */
final class RateLimiter
{
    public static function tooMany(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $now = time();
        $sessionKey = '_rate_' . $key;
        /** @var list<int> $attempts */
        $attempts = Session::get($sessionKey, []);
        $attempts = array_values(array_filter($attempts, static fn (int $t): bool => $t > $now - $windowSeconds));

        if (count($attempts) >= $maxAttempts) {
            return true;
        }

        $attempts[] = $now;
        Session::set($sessionKey, $attempts);
        return false;
    }

    public static function clear(string $key): void
    {
        Session::forget('_rate_' . $key);
    }
}
