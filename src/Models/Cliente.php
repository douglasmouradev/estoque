<?php

declare(strict_types=1);

namespace App\Models;

final class Cliente extends Model
{
    private const COLS = 'id, nome, cpf_cnpj, telefone, email, logradouro, numero, complemento, bairro, cidade, uf, cep, created_at, updated_at';

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ' FROM clientes WHERE id = :id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @param array<string, mixed> $query */
    public static function listar(array $query): array
    {
        $p = self::paginacaoParams($query, ['id', 'nome', 'cpf_cnpj', 'created_at'], 'nome');
        $where = 'deleted_at IS NULL';
        $params = [];

        if ($p['search'] !== '') {
            $where .= ' AND (nome LIKE :q OR cpf_cnpj LIKE :q2 OR telefone LIKE :q3)';
            $like = '%' . $p['search'] . '%';
            $params['q'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        $countStmt = self::pdo()->prepare("SELECT COUNT(*) FROM clientes WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT ' . self::COLS . " FROM clientes WHERE {$where}
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

    /** @param array<string, mixed> $dados */
    public static function criar(array $dados, ?int $userId): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO clientes (nome, cpf_cnpj, telefone, email, logradouro, numero, complemento, bairro, cidade, uf, cep, created_by)
             VALUES (:nome, :cpf_cnpj, :telefone, :email, :logradouro, :numero, :complemento, :bairro, :cidade, :uf, :cep, :created_by)'
        );
        $stmt->execute([
            'nome' => $dados['nome'],
            'cpf_cnpj' => $dados['cpf_cnpj'],
            'telefone' => $dados['telefone'] ?? null,
            'email' => $dados['email'] ?? null,
            'logradouro' => $dados['logradouro'] ?? null,
            'numero' => $dados['numero'] ?? null,
            'complemento' => $dados['complemento'] ?? null,
            'bairro' => $dados['bairro'] ?? null,
            'cidade' => $dados['cidade'] ?? null,
            'uf' => $dados['uf'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'created_by' => $userId,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $dados */
    public static function atualizar(int $id, array $dados): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE clientes SET nome = :nome, cpf_cnpj = :cpf_cnpj, telefone = :telefone, email = :email,
             logradouro = :logradouro, numero = :numero, complemento = :complemento, bairro = :bairro,
             cidade = :cidade, uf = :uf, cep = :cep WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'cpf_cnpj' => $dados['cpf_cnpj'],
            'telefone' => $dados['telefone'] ?? null,
            'email' => $dados['email'] ?? null,
            'logradouro' => $dados['logradouro'] ?? null,
            'numero' => $dados['numero'] ?? null,
            'complemento' => $dados['complemento'] ?? null,
            'bairro' => $dados['bairro'] ?? null,
            'cidade' => $dados['cidade'] ?? null,
            'uf' => $dados['uf'] ?? null,
            'cep' => $dados['cep'] ?? null,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $stmt = self::pdo()->prepare('UPDATE clientes SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
    }

    /** Autocomplete para orçamentos */
    public static function buscar(string $termo, int $limite = 15): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT id, nome, cpf_cnpj, telefone FROM clientes
             WHERE deleted_at IS NULL AND (nome LIKE :q OR cpf_cnpj LIKE :q2)
             ORDER BY nome ASC LIMIT :lim'
        );
        $like = '%' . $termo . '%';
        $stmt->bindValue('q', $like);
        $stmt->bindValue('q2', $like);
        $stmt->bindValue('lim', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
