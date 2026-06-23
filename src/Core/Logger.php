<?php

declare(strict_types=1);

namespace App\Core;

final class Logger
{
    private static function path(): string
    {
        return dirname(__DIR__, 2) . '/storage/logs/app.log';
    }

    public static function info(string $msg, array $ctx = []): void
    {
        self::write('INFO', $msg, $ctx);
    }

    public static function error(string $msg, array $ctx = []): void
    {
        self::write('ERROR', $msg, $ctx);
    }

    private static function write(string $level, string $msg, array $ctx): void
    {
        $dir = dirname(self::path());
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $line = sprintf(
            "[%s] %s %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $msg,
            $ctx !== [] ? json_encode($ctx, JSON_UNESCAPED_UNICODE) : ''
        );
        file_put_contents(self::path(), $line, FILE_APPEND | LOCK_EX);
    }
}
