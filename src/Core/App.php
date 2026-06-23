<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
    private static array $config = [];

    public static function bootstrap(bool $connectDatabase = true): void
    {
        $root = dirname(__DIR__, 2);
        self::loadEnv($root . '/.env');

        self::$config['app'] = require $root . '/config/app.php';
        self::$config['database'] = require $root . '/config/database.php';

        date_default_timezone_set(self::$config['app']['timezone']);

        self::ensureStorageDirs();

        if ($connectDatabase) {
            $autoDb = filter_var(
                $_ENV['APP_AUTO_CREATE_DB'] ?? (self::$config['app']['debug'] ? 'true' : 'false'),
                FILTER_VALIDATE_BOOLEAN
            );
            if ($autoDb) {
                self::ensureDatabaseExists();
            }
            Session::start(self::$config['app']);
            Database::connect(self::$config['database']);
        }
        View::setBasePath($root . '/src/Views');

        \App\Services\OrcamentoService::bootListeners();

        if (self::$config['app']['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', '0');
        }
    }

    public static function config(string $key): mixed
    {
        return self::$config[$key] ?? null;
    }

    public static function ensureStorageDirs(): void
    {
        $root = dirname(__DIR__, 2);
        foreach (['uploads', 'pdfs', 'logs'] as $sub) {
            $dir = $root . '/storage/' . $sub;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public static function ensureDatabaseExists(): void
    {
        $db = self::$config['database'];
        $dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $db['host'], $db['port'], $db['charset']);
        $pdo = new \PDO($dsn, $db['username'], $db['password'], $db['options']);
        $name = str_replace('`', '``', $db['database']);
        $pdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    private static function loadEnv(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\"'");
            if ($name !== '' && !array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}
