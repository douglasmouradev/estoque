<?php

declare(strict_types=1);

namespace App\Models;

final class CategoriaPeca extends Model
{
    public static function listar(): array
    {
        $stmt = self::pdo()->query('SELECT id, nome FROM categorias_pecas ORDER BY nome ASC');
        return $stmt->fetchAll();
    }

    public static function criar(string $nome): int
    {
        $stmt = self::pdo()->prepare('INSERT INTO categorias_pecas (nome) VALUES (:nome)');
        $stmt->execute(['nome' => $nome]);
        return (int) self::pdo()->lastInsertId();
    }

    public static function atualizar(int $id, string $nome): void
    {
        $stmt = self::pdo()->prepare('UPDATE categorias_pecas SET nome = :nome WHERE id = :id');
        $stmt->execute(['id' => $id, 'nome' => $nome]);
    }

    public static function remover(int $id): void
    {
        $stmt = self::pdo()->prepare('DELETE FROM categorias_pecas WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
