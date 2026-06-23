<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connect(array $config): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            $hint = '';
            if (str_contains($e->getMessage(), '1045')) {
                $hint = ' Verifique DB_USERNAME e DB_PASSWORD no arquivo .env (raiz do projeto).';
            }
            if (str_contains($e->getMessage(), 'Unknown database')) {
                $hint = ' Crie o banco com: CREATE DATABASE oficina_estoque; depois rode php bin/migrate.php';
            }
            throw new PDOException(
                'Falha na conexão com o banco: ' . $e->getMessage() . $hint,
                (int) $e->getCode(),
                $e
            );
        }

        return self::$connection;
    }

    public static function pdo(): PDO
    {
        if (self::$connection === null) {
            throw new \RuntimeException('Database não inicializado. Chame Database::connect() primeiro.');
        }

        return self::$connection;
    }

    /** Para testes — permite reinjetar conexão. */
    public static function setConnection(?PDO $pdo): void
    {
        self::$connection = $pdo;
    }
}
