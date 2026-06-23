<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MotivoMovimentacao;
use App\Enums\UnidadePeca;

final class Peca extends Model
{
    private const COLS = 'p.id, p.codigo_interno, p.codigo_oem, p.descricao, p.unidade, p.categoria_id,
        p.marca, p.localizacao, p.estoque_minimo, p.preco_venda, p.created_at, p.updated_at';

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ', c.nome AS categoria_nome,
                    (' . self::subquerySaldo() . ') AS estoque_atual
             FROM pecas p
             LEFT JOIN categorias_pecas c ON c.id = p.categoria_id
             WHERE p.id = :id AND p.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Saldo = entradas - saídas via subquery reutilizável */
    private static function subquerySaldo(): string
    {
        return '(SELECT COALESCE(SUM(
            CASE WHEN m.tipo = \'entrada\' THEN m.quantidade ELSE -m.quantidade END
        ), 0) FROM movimentacoes_estoque m WHERE m.peca_id = p.id)';
    }

    /** @param array<string, mixed> $query */
    public static function listar(array $query): array
    {
        $p = self::paginacaoParams($query, ['codigo_interno', 'descricao', 'marca', 'estoque_atual'], 'descricao');
        $where = 'p.deleted_at IS NULL';
        $params = [];

        if ($p['search'] !== '') {
            $where .= ' AND (p.codigo_interno LIKE :q OR p.codigo_oem LIKE :q2 OR p.descricao LIKE :q3)';
            $like = '%' . $p['search'] . '%';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like];
        }

        if (!empty($query['abaixo_minimo'])) {
            $where .= ' AND (' . self::subquerySaldo() . ') <= p.estoque_minimo';
        }

        $countSql = "SELECT COUNT(*) FROM pecas p WHERE {$where}";
        $countStmt = self::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $orderCol = $p['sort'] === 'estoque_atual' ? self::subquerySaldo() : 'p.' . $p['sort'];
        $sql = 'SELECT ' . self::COLS . ', c.nome AS categoria_nome, ' . self::subquerySaldo() . ' AS estoque_atual
                FROM pecas p
                LEFT JOIN categorias_pecas c ON c.id = p.categoria_id
                WHERE ' . $where . " ORDER BY {$orderCol} {$p['dir']} LIMIT :limit OFFSET :offset";

        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $p['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue('offset', $p['offset'], \PDO::PARAM_INT);
        $stmt->execute();

        return self::paginacaoResposta($stmt->fetchAll(), $total, $p);
    }

    public static function buscarAutocomplete(string $termo, int $limite = 20): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT p.id, p.codigo_interno, p.codigo_oem, p.descricao, p.preco_venda, p.unidade,
                    ' . self::subquerySaldo() . ' AS estoque_atual
             FROM pecas p
             WHERE p.deleted_at IS NULL
               AND (p.codigo_interno LIKE :q OR p.codigo_oem LIKE :q2 OR p.descricao LIKE :q3)
             ORDER BY p.descricao ASC
             LIMIT :lim'
        );
        $like = '%' . $termo . '%';
        $stmt->bindValue('q', $like);
        $stmt->bindValue('q2', $like);
        $stmt->bindValue('q3', $like);
        $stmt->bindValue('lim', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Histórico com saldo acumulado — CTE no MySQL 8 */
    public static function historicoMovimentacoes(int $pecaId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $sql = 'WITH mov AS (
            SELECT m.id, m.tipo, m.quantidade, m.motivo, m.observacao, m.created_at, m.ordem_servico_id,
                   u.nome AS usuario_nome,
                   CASE WHEN m.tipo = \'entrada\' THEN m.quantidade ELSE -m.quantidade END AS delta
            FROM movimentacoes_estoque m
            LEFT JOIN users u ON u.id = m.created_by
            WHERE m.peca_id = :peca_id
        ),
        saldos AS (
            SELECT mov.*, SUM(delta) OVER (ORDER BY created_at, id) AS saldo_apos
            FROM mov
        )
        SELECT id, tipo, quantidade, motivo, observacao, created_at, ordem_servico_id, usuario_nome, saldo_apos
        FROM saldos
        ORDER BY created_at DESC, id DESC
        LIMIT :limit OFFSET :offset';

        $stmt = self::pdo()->prepare($sql);
        $stmt->bindValue('peca_id', $pecaId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $itens = $stmt->fetchAll();

        $count = self::pdo()->prepare('SELECT COUNT(*) FROM movimentacoes_estoque WHERE peca_id = :id');
        $count->execute(['id' => $pecaId]);
        $total = (int) $count->fetchColumn();

        return ['itens' => $itens, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    /** Peças sem movimentação nos últimos N dias */
    public static function paradas(int $dias): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT p.id, p.codigo_interno, p.descricao, p.localizacao,
                    (' . self::subquerySaldo() . ') AS estoque_atual,
                    MAX(m.created_at) AS ultima_movimentacao
             FROM pecas p
             LEFT JOIN movimentacoes_estoque m ON m.peca_id = p.id
             WHERE p.deleted_at IS NULL
             GROUP BY p.id, p.codigo_interno, p.descricao, p.localizacao, p.estoque_minimo
             HAVING ultima_movimentacao IS NULL OR ultima_movimentacao < DATE_SUB(NOW(), INTERVAL :dias DAY)
             ORDER BY ultima_movimentacao ASC'
        );
        $stmt->execute(['dias' => $dias]);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $dados */
    public static function criar(array $dados, ?int $userId): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO pecas (codigo_interno, codigo_oem, descricao, unidade, categoria_id, marca, localizacao, estoque_minimo, preco_venda, created_by)
             VALUES (:codigo_interno, :codigo_oem, :descricao, :unidade, :categoria_id, :marca, :localizacao, :estoque_minimo, :preco_venda, :created_by)'
        );
        $stmt->execute([
            'codigo_interno' => $dados['codigo_interno'],
            'codigo_oem' => $dados['codigo_oem'] ?? null,
            'descricao' => $dados['descricao'],
            'unidade' => $dados['unidade'],
            'categoria_id' => $dados['categoria_id'] ?? null,
            'marca' => $dados['marca'] ?? null,
            'localizacao' => $dados['localizacao'] ?? null,
            'estoque_minimo' => $dados['estoque_minimo'] ?? 0,
            'preco_venda' => $dados['preco_venda'] ?? 0,
            'created_by' => $userId,
        ]);
        $pecaId = (int) self::pdo()->lastInsertId();
        $saldoInicial = (float) ($dados['estoque_inicial'] ?? 0);
        if ($saldoInicial > 0) {
            MovimentacaoEstoque::registrar(
                $pecaId,
                MotivoMovimentacao::Compra,
                $saldoInicial,
                null,
                'Saldo inicial no cadastro',
                $userId,
            );
        }
        return $pecaId;
    }

    /** @param array<string, mixed> $dados */
    public static function atualizar(int $id, array $dados): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE pecas SET codigo_interno = :codigo_interno, codigo_oem = :codigo_oem, descricao = :descricao,
             unidade = :unidade, categoria_id = :categoria_id, marca = :marca, localizacao = :localizacao,
             estoque_minimo = :estoque_minimo, preco_venda = :preco_venda
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'codigo_interno' => $dados['codigo_interno'],
            'codigo_oem' => $dados['codigo_oem'] ?? null,
            'descricao' => $dados['descricao'],
            'unidade' => $dados['unidade'],
            'categoria_id' => $dados['categoria_id'] ?? null,
            'marca' => $dados['marca'] ?? null,
            'localizacao' => $dados['localizacao'] ?? null,
            'estoque_minimo' => $dados['estoque_minimo'] ?? 0,
            'preco_venda' => $dados['preco_venda'] ?? 0,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $stmt = self::pdo()->prepare('UPDATE pecas SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
    }

    public static function saldoAtual(int $pecaId): float
    {
        $stmt = self::pdo()->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN quantidade ELSE -quantidade END), 0)
             FROM movimentacoes_estoque WHERE peca_id = :id"
        );
        $stmt->execute(['id' => $pecaId]);
        return (float) $stmt->fetchColumn();
    }
}
