<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $t = Session::get(self::KEY);
        if (!is_string($t) || $t === '') {
            $t = bin2hex(random_bytes(32));
            Session::set(self::KEY, $t);
        }
        return $t;
    }

    public static function validate(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }
        $stored = Session::get(self::KEY);
        return is_string($stored) && hash_equals($stored, $token);
    }

    public static function validateRequest(Request $request): bool
    {
        $token = $request->string('_token')
            ?: $request->string('csrf_token')
            ?: ($request->server['HTTP_X_CSRF_TOKEN'] ?? '');
        return self::validate($token);
    }
}
