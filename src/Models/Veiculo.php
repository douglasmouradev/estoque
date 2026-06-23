<?php

declare(strict_types=1);

namespace App\Models;

final class Veiculo extends Model
{
    private const COLS = 'id, cliente_id, placa, chassi, marca, modelo, ano, cor, km_atual, created_at, updated_at';

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ' FROM veiculos WHERE id = :id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByPlaca(string $placa): ?array
    {
        $placaNorm = self::normalizarPlaca($placa);
        $stmt = self::pdo()->prepare(
            'SELECT v.id, v.cliente_id, v.placa, v.chassi, v.marca, v.modelo, v.ano, v.cor, v.km_atual,
                    c.nome AS cliente_nome
             FROM veiculos v
             INNER JOIN clientes c ON c.id = v.cliente_id AND c.deleted_at IS NULL
             WHERE v.placa = :placa AND v.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['placa' => $placaNorm]);
        return $stmt->fetch() ?: null;
    }

    /** @param array<string, mixed> $query */
    public static function listarPorCliente(int $clienteId, array $query = []): array
    {
        $p = self::paginacaoParams($query, ['placa', 'marca', 'modelo', 'ano'], 'placa');
        $params = ['cliente_id' => $clienteId];

        $countStmt = self::pdo()->prepare(
            'SELECT COUNT(*) FROM veiculos WHERE cliente_id = :cliente_id AND deleted_at IS NULL'
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT ' . self::COLS . ' FROM veiculos
                WHERE cliente_id = :cliente_id AND deleted_at IS NULL
                ORDER BY ' . $p['sort'] . ' ' . $p['dir'] . ' LIMIT :limit OFFSET :offset';
        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue('cliente_id', $clienteId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $p['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue('offset', $p['offset'], \PDO::PARAM_INT);
        $stmt->execute();

        return self::paginacaoResposta($stmt->fetchAll(), $total, $p);
    }

    /** @param array<string, mixed> $dados */
    public static function criar(array $dados, ?int $userId): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO veiculos (cliente_id, placa, chassi, marca, modelo, ano, cor, km_atual, created_by)
             VALUES (:cliente_id, :placa, :chassi, :marca, :modelo, :ano, :cor, :km_atual, :created_by)'
        );
        $stmt->execute([
            'cliente_id' => $dados['cliente_id'],
            'placa' => self::normalizarPlaca($dados['placa']),
            'chassi' => $dados['chassi'] ?? null,
            'marca' => $dados['marca'],
            'modelo' => $dados['modelo'],
            'ano' => $dados['ano'] ?? null,
            'cor' => $dados['cor'] ?? null,
            'km_atual' => $dados['km_atual'] ?? 0,
            'created_by' => $userId,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $dados */
    public static function atualizar(int $id, array $dados): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE veiculos SET placa = :placa, chassi = :chassi, marca = :marca, modelo = :modelo,
             ano = :ano, cor = :cor, km_atual = :km_atual
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'placa' => self::normalizarPlaca($dados['placa']),
            'chassi' => $dados['chassi'] ?? null,
            'marca' => $dados['marca'],
            'modelo' => $dados['modelo'],
            'ano' => $dados['ano'] ?? null,
            'cor' => $dados['cor'] ?? null,
            'km_atual' => $dados['km_atual'] ?? 0,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $stmt = self::pdo()->prepare('UPDATE veiculos SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
    }

    public static function normalizarPlaca(string $placa): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placa) ?? '');
    }
}
