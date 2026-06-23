<?php

declare(strict_types=1);

namespace App\Models;

final class Fornecedor extends Model
{
    private const COLS = 'id, razao_social, nome_fantasia, cnpj, telefone, email, created_at';

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ' FROM fornecedores WHERE id = :id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** @param array<string, mixed> $query */
    public static function listar(array $query = []): array
    {
        $p = self::paginacaoParams($query, ['razao_social', 'created_at'], 'razao_social');
        $where = 'deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (razao_social LIKE :q OR nome_fantasia LIKE :q2 OR cnpj LIKE :q3)';
            $like = '%' . $p['search'] . '%';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like];
        }
        $count = self::pdo()->prepare("SELECT COUNT(*) FROM fornecedores WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = 'SELECT ' . self::COLS . " FROM fornecedores WHERE {$where}
                ORDER BY {$p['sort']} {$p['dir']} LIMIT :limit OFFSET :offset";
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $p['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue('offset', $p['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        return self::paginacaoResposta($stmt->fetchAll(), $total, $p);
    }

    public static function listarTodos(): array
    {
        $stmt = self::pdo()->query(
            'SELECT id, razao_social, nome_fantasia FROM fornecedores WHERE deleted_at IS NULL ORDER BY razao_social ASC'
        );
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $dados */
    public static function criar(array $dados, ?int $userId): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO fornecedores (razao_social, nome_fantasia, cnpj, telefone, email, created_by)
             VALUES (:rs, :nf, :cnpj, :tel, :email, :uid)'
        );
        $stmt->execute([
            'rs' => $dados['razao_social'],
            'nf' => $dados['nome_fantasia'] ?? null,
            'cnpj' => $dados['cnpj'] ?? null,
            'tel' => $dados['telefone'] ?? null,
            'email' => $dados['email'] ?? null,
            'uid' => $userId,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $dados */
    public static function atualizar(int $id, array $dados): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE fornecedores SET razao_social = :rs, nome_fantasia = :nf, cnpj = :cnpj,
             telefone = :tel, email = :email WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'rs' => $dados['razao_social'],
            'nf' => $dados['nome_fantasia'] ?? null,
            'cnpj' => $dados['cnpj'] ?? null,
            'tel' => $dados['telefone'] ?? null,
            'email' => $dados['email'] ?? null,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $stmt = self::pdo()->prepare('UPDATE fornecedores SET deleted_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
