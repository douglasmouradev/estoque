<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Enums\StatusOrcamento;
use App\Enums\TipoItemOrcamento;

final class Orcamento extends Model
{
    private const COLS = 'o.id, o.numero, o.versao, o.cliente_id, o.veiculo_id, o.status,
        o.desconto_geral_percent, o.desconto_geral_valor, o.observacao_interna, o.observacao_cliente,
        o.token_acesso, o.token_expira_em,
        o.aprovado_em, o.reprovado_em, o.created_at, o.updated_at';

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ', c.nome AS cliente_nome, c.email AS cliente_email, v.placa, v.marca, v.modelo
             FROM orcamentos o
             INNER JOIN clientes c ON c.id = o.cliente_id
             INNER JOIN veiculos v ON v.id = o.veiculo_id
             WHERE o.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function itens(int $orcamentoId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT oi.id, oi.tipo, oi.peca_id, oi.descricao, oi.quantidade, oi.preco_unitario,
                    oi.desconto_percent, oi.desconto_valor, oi.ordem, p.codigo_interno,
                    (SELECT COALESCE(SUM(CASE WHEN m.tipo = \'entrada\' THEN m.quantidade ELSE -m.quantidade END), 0)
                     FROM movimentacoes_estoque m WHERE m.peca_id = oi.peca_id) AS estoque_atual
             FROM orcamento_itens oi
             LEFT JOIN pecas p ON p.id = oi.peca_id AND p.deleted_at IS NULL
             WHERE oi.orcamento_id = :id
             ORDER BY oi.ordem ASC, oi.id ASC'
        );
        $stmt->execute(['id' => $orcamentoId]);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $query */
    public static function listar(array $query): array
    {
        $p = self::paginacaoParams($query, ['numero', 'status', 'created_at'], 'created_at');
        $where = '1=1';
        $params = [];

        if ($p['search'] !== '') {
            $where .= ' AND (c.nome LIKE :q OR CAST(o.numero AS CHAR) LIKE :q2)';
            $like = '%' . $p['search'] . '%';
            $params['q'] = $like;
            $params['q2'] = $like;
        }
        if (!empty($query['status'])) {
            $where .= ' AND o.status = :status';
            $params['status'] = $query['status'];
        }

        $countStmt = self::pdo()->prepare(
            "SELECT COUNT(*) FROM orcamentos o INNER JOIN clientes c ON c.id = o.cliente_id WHERE {$where}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sort = $p['sort'] === 'numero' ? 'o.numero' : ($p['sort'] === 'status' ? 'o.status' : 'o.created_at');
        $sql = 'SELECT ' . self::COLS . ', c.nome AS cliente_nome, c.email AS cliente_email, v.placa
                FROM orcamentos o
                INNER JOIN clientes c ON c.id = o.cliente_id
                INNER JOIN veiculos v ON v.id = o.veiculo_id
                WHERE ' . $where . " ORDER BY {$sort} {$p['dir']} LIMIT :limit OFFSET :offset";

        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $p['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue('offset', $p['offset'], \PDO::PARAM_INT);
        $stmt->execute();

        return self::paginacaoResposta($stmt->fetchAll(), $total, $p);
    }

    public static function proximoNumero(): int
    {
        $stmt = self::pdo()->query('SELECT COALESCE(MAX(numero), 0) + 1 FROM orcamentos');
        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $cabecalho */
    public static function criar(array $cabecalho, ?int $userId): int
    {
        $numero = self::proximoNumero();
        $stmt = self::pdo()->prepare(
            'INSERT INTO orcamentos (numero, versao, cliente_id, veiculo_id, status, observacao_interna, created_by)
             VALUES (:numero, 1, :cliente_id, :veiculo_id, :status, :obs, :created_by)'
        );
        $stmt->execute([
            'numero' => $numero,
            'cliente_id' => $cabecalho['cliente_id'],
            'veiculo_id' => $cabecalho['veiculo_id'],
            'status' => StatusOrcamento::Rascunho->value,
            'obs' => $cabecalho['observacao_interna'] ?? null,
            'created_by' => $userId,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** Se aprovado for editado, grava snapshot da versão anterior */
    public static function salvarComVersionamento(int $id, array $cabecalho, array $itens): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $atual = self::findById($id);
            if ($atual === null) {
                throw new \RuntimeException('Orçamento não encontrado.');
            }

            if ($atual['status'] === StatusOrcamento::Aprovado->value) {
                $snapshot = json_encode([
                    'orcamento' => $atual,
                    'itens' => self::itens($id),
                ], JSON_THROW_ON_ERROR);

                $stmtV = $pdo->prepare(
                    'INSERT INTO orcamento_versoes (orcamento_id, versao_anterior, snapshot, created_by)
                     VALUES (:oid, :ver, :snap, :uid)'
                );
                $stmtV->execute([
                    'oid' => $id,
                    'ver' => $atual['versao'],
                    'snap' => $snapshot,
                    'uid' => Auth::id(),
                ]);

                $upd = $pdo->prepare('UPDATE orcamentos SET versao = versao + 1 WHERE id = :id');
                $upd->execute(['id' => $id]);
            }

            $stmt = $pdo->prepare(
                'UPDATE orcamentos SET cliente_id = :cid, veiculo_id = :vid,
                 desconto_geral_percent = :dp, desconto_geral_valor = :dv, observacao_interna = :obs
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'cid' => $cabecalho['cliente_id'],
                'vid' => $cabecalho['veiculo_id'],
                'dp' => $cabecalho['desconto_geral_percent'] ?? 0,
                'dv' => $cabecalho['desconto_geral_valor'] ?? 0,
                'obs' => $cabecalho['observacao_interna'] ?? null,
            ]);

            $pdo->prepare('DELETE FROM orcamento_itens WHERE orcamento_id = :id')->execute(['id' => $id]);
            self::inserirItens($pdo, $id, $itens);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @param list<array<string, mixed>> $itens */
    private static function inserirItens(\PDO $pdo, int $orcamentoId, array $itens): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO orcamento_itens (orcamento_id, tipo, peca_id, descricao, quantidade, preco_unitario, desconto_percent, desconto_valor, ordem)
             VALUES (:oid, :tipo, :peca_id, :desc, :qtd, :preco, :dp, :dv, :ordem)'
        );
        $ordem = 0;
        foreach ($itens as $item) {
            $stmt->execute([
                'oid' => $orcamentoId,
                'tipo' => $item['tipo'],
                'peca_id' => $item['peca_id'] ?? null,
                'desc' => $item['descricao'],
                'qtd' => $item['quantidade'],
                'preco' => $item['preco_unitario'],
                'dp' => $item['desconto_percent'] ?? 0,
                'dv' => $item['desconto_valor'] ?? 0,
                'ordem' => $ordem++,
            ]);
        }
    }

    public static function calcularTotais(array $itens, float $descGeralPct, float $descGeralVal): array
    {
        $subtotal = 0.0;
        foreach ($itens as $item) {
            $bruto = (float) $item['quantidade'] * (float) $item['preco_unitario'];
            $desc = max((float) ($item['desconto_valor'] ?? 0), $bruto * ((float) ($item['desconto_percent'] ?? 0) / 100));
            $subtotal += $bruto - $desc;
        }
        $descGeral = max($descGeralVal, $subtotal * ($descGeralPct / 100));
        $total = max(0, $subtotal - $descGeral);
        return ['subtotal' => round($subtotal, 2), 'desconto_geral' => round($descGeral, 2), 'total' => round($total, 2)];
    }

    public static function alterarStatus(int $id, StatusOrcamento $status, ?string $obsCliente = null): void
    {
        $extra = match ($status) {
            StatusOrcamento::Aprovado => ', aprovado_em = NOW(), reprovado_em = NULL',
            StatusOrcamento::Reprovado => ', reprovado_em = NOW(), aprovado_em = NULL',
            default => '',
        };
        $stmt = self::pdo()->prepare(
            "UPDATE orcamentos SET status = :status, observacao_cliente = COALESCE(:obs, observacao_cliente) {$extra} WHERE id = :id"
        );
        $stmt->execute(['id' => $id, 'status' => $status->value, 'obs' => $obsCliente]);
    }

    public static function versoes(int $orcamentoId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT id, versao_anterior, created_at FROM orcamento_versoes WHERE orcamento_id = :id ORDER BY created_at DESC'
        );
        $stmt->execute(['id' => $orcamentoId]);
        return $stmt->fetchAll();
    }

    public static function definirToken(int $id, string $token, int $diasValidade): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE orcamentos SET token_acesso = :tok, token_expira_em = DATE_ADD(NOW(), INTERVAL :dias DAY) WHERE id = :id'
        );
        $stmt->execute(['tok' => $token, 'dias' => $diasValidade, 'id' => $id]);
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ', c.nome AS cliente_nome, c.email AS cliente_email, v.placa, v.marca, v.modelo
             FROM orcamentos o
             INNER JOIN clientes c ON c.id = o.cliente_id
             INNER JOIN veiculos v ON v.id = o.veiculo_id
             WHERE o.token_acesso = :tok AND (o.token_expira_em IS NULL OR o.token_expira_em >= NOW())
             LIMIT 1'
        );
        $stmt->execute(['tok' => $token]);
        return $stmt->fetch() ?: null;
    }
}
