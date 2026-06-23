<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Enums\MotivoMovimentacao;
use App\Services\FinanceiroService;
use App\Enums\StatusOrdemServico;
use App\Enums\StatusOrcamento;
use App\Enums\TipoItemOrcamento;

final class OrdemServico extends Model
{
    private const COLS = 'os.id, os.numero, os.orcamento_id, os.cliente_id, os.veiculo_id, os.status,
        os.observacoes, os.valor_total, os.valor_pago, os.status_pagamento,
        os.finalizada_em, os.created_at, os.updated_at';

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ', c.nome AS cliente_nome, v.placa, v.marca, v.modelo
             FROM ordens_servico os
             INNER JOIN clientes c ON c.id = os.cliente_id
             INNER JOIN veiculos v ON v.id = os.veiculo_id
             WHERE os.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function proximoNumero(): int
    {
        $stmt = self::pdo()->query('SELECT COALESCE(MAX(numero), 0) + 1 FROM ordens_servico');
        return (int) $stmt->fetchColumn();
    }

    public static function criarDeOrcamento(int $orcamentoId, ?int $userId): int
    {
        $orc = Orcamento::findById($orcamentoId);
        if ($orc === null || $orc['status'] !== StatusOrcamento::Aprovado->value) {
            throw new \RuntimeException('Orçamento deve estar aprovado para gerar OS.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $numero = self::proximoNumero();
            $stmt = $pdo->prepare(
                'INSERT INTO ordens_servico (numero, orcamento_id, cliente_id, veiculo_id, status, created_by)
                 VALUES (:numero, :orc_id, :cid, :vid, :status, :uid)'
            );
            $stmt->execute([
                'numero' => $numero,
                'orc_id' => $orcamentoId,
                'cid' => $orc['cliente_id'],
                'vid' => $orc['veiculo_id'],
                'status' => StatusOrdemServico::Aberta->value,
                'uid' => $userId,
            ]);
            $osId = (int) $pdo->lastInsertId();

            $itens = Orcamento::itens($orcamentoId);
            $ins = $pdo->prepare(
                'INSERT INTO os_itens (ordem_servico_id, orcamento_item_id, tipo, peca_id, descricao, quantidade, preco_unitario)
                 VALUES (:os, :oi_id, :tipo, :peca, :desc, :qtd, :preco)'
            );
            foreach ($itens as $item) {
                $ins->execute([
                    'os' => $osId,
                    'oi_id' => $item['id'],
                    'tipo' => $item['tipo'],
                    'peca' => $item['peca_id'],
                    'desc' => $item['descricao'],
                    'qtd' => $item['quantidade'],
                    'preco' => $item['preco_unitario'],
                ]);
            }

            Orcamento::alterarStatus($orcamentoId, StatusOrcamento::Convertido);

            $pdo->commit();
            return $osId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function itens(int $osId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT id, tipo, peca_id, descricao, quantidade, preco_unitario, concluido
             FROM os_itens WHERE ordem_servico_id = :id ORDER BY id ASC'
        );
        $stmt->execute(['id' => $osId]);
        return $stmt->fetchAll();
    }

    public static function checklist(int $osId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT id, descricao, concluido, ordem FROM os_checklist WHERE ordem_servico_id = :id ORDER BY ordem ASC'
        );
        $stmt->execute(['id' => $osId]);
        return $stmt->fetchAll();
    }

    public static function horas(int $osId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT h.id, h.data_trabalho, h.horas, h.descricao, u.nome AS mecanico_nome
             FROM os_horas h
             INNER JOIN users u ON u.id = h.mecanico_id
             WHERE h.ordem_servico_id = :id ORDER BY h.data_trabalho DESC'
        );
        $stmt->execute(['id' => $osId]);
        return $stmt->fetchAll();
    }

    /** Finaliza OS e baixa estoque das peças */
    public static function finalizar(int $osId, ?int $userId): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $os = self::findById($osId);
            if ($os === null) {
                throw new \RuntimeException('OS não encontrada.');
            }
            if ($os['status'] === StatusOrdemServico::Finalizada->value) {
                throw new \RuntimeException('OS já finalizada.');
            }
            if ($os['status'] === StatusOrdemServico::Cancelada->value) {
                throw new \RuntimeException('OS cancelada não pode ser finalizada.');
            }

            $itens = self::itens($osId);
            foreach ($itens as $item) {
                if ($item['tipo'] === TipoItemOrcamento::Peca->value && $item['peca_id'] && !empty($item['concluido'])) {
                    MovimentacaoEstoque::registrar(
                        (int) $item['peca_id'],
                        MotivoMovimentacao::UsoOs,
                        (float) $item['quantidade'],
                        $osId,
                        'Baixa automática OS #' . $os['numero'],
                        $userId,
                    );
                }
            }

            $upd = $pdo->prepare(
                "UPDATE ordens_servico SET status = :st, finalizada_em = NOW() WHERE id = :id"
            );
            $upd->execute(['id' => $osId, 'st' => StatusOrdemServico::Finalizada->value]);

            $pdo->commit();
            FinanceiroService::atualizarTotal($osId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function atualizarStatus(int $id, StatusOrdemServico $status, ?int $userId = null): void
    {
        $os = self::findById($id);
        if ($os === null) {
            throw new \RuntimeException('OS não encontrada.');
        }

        $atual = StatusOrdemServico::from($os['status']);
        if ($atual === $status) {
            return;
        }

        if ($atual === StatusOrdemServico::Finalizada && $status !== StatusOrdemServico::Cancelada) {
            throw new \RuntimeException('OS finalizada só pode ser cancelada (com estorno de estoque).');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            if ($status === StatusOrdemServico::Cancelada && $atual === StatusOrdemServico::Finalizada) {
                self::estornarEstoque($id, (int) $os['numero'], $userId);
            }

            if ($status === StatusOrdemServico::Cancelada) {
                $sql = 'UPDATE ordens_servico SET status = :st, finalizada_em = NULL WHERE id = :id';
            } elseif ($status === StatusOrdemServico::Finalizada) {
                $sql = 'UPDATE ordens_servico SET status = :st, finalizada_em = COALESCE(finalizada_em, NOW()) WHERE id = :id';
            } else {
                $sql = 'UPDATE ordens_servico SET status = :st WHERE id = :id';
            }

            $pdo->prepare($sql)->execute(['id' => $id, 'st' => $status->value]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function estornarEstoque(int $osId, int $numero, ?int $userId): void
    {
        foreach (self::itens($osId) as $item) {
            if ($item['tipo'] !== TipoItemOrcamento::Peca->value || !$item['peca_id'] || empty($item['concluido'])) {
                continue;
            }
            MovimentacaoEstoque::registrar(
                (int) $item['peca_id'],
                MotivoMovimentacao::CancelamentoOs,
                (float) $item['quantidade'],
                $osId,
                'Estorno automático — OS #' . $numero . ' cancelada',
                $userId,
            );
        }
    }

    public static function assertEditavel(int $osId): array
    {
        $os = self::findById($osId);
        if ($os === null) {
            throw new \RuntimeException('OS não encontrada.');
        }
        if (in_array($os['status'], [StatusOrdemServico::Finalizada->value, StatusOrdemServico::Cancelada->value], true)) {
            throw new \RuntimeException('OS finalizada ou cancelada não pode ser alterada.');
        }
        return $os;
    }

    public static function adicionarItem(
        int $osId,
        string $tipo,
        string $descricao,
        float $quantidade,
        float $precoUnitario,
        ?int $pecaId,
    ): int {
        self::assertEditavel($osId);
        if ($descricao === '') {
            throw new \InvalidArgumentException('Descrição é obrigatória.');
        }
        if ($quantidade <= 0) {
            throw new \InvalidArgumentException('Quantidade deve ser maior que zero.');
        }

        $stmt = self::pdo()->prepare(
            'INSERT INTO os_itens (ordem_servico_id, tipo, peca_id, descricao, quantidade, preco_unitario)
             VALUES (:os, :tipo, :peca, :desc, :qtd, :preco)'
        );
        $stmt->execute([
            'os' => $osId,
            'tipo' => $tipo,
            'peca' => $pecaId,
            'desc' => $descricao,
            'qtd' => $quantidade,
            'preco' => $precoUnitario,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    public static function removerItem(int $itemId): void
    {
        $stmt = self::pdo()->prepare(
            'SELECT oi.ordem_servico_id FROM os_itens oi WHERE oi.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $itemId]);
        $row = $stmt->fetch();
        if ($row === false) {
            throw new \RuntimeException('Item não encontrado.');
        }
        self::assertEditavel((int) $row['ordem_servico_id']);
        self::pdo()->prepare('DELETE FROM os_itens WHERE id = :id')->execute(['id' => $itemId]);
    }

    public static function toggleItem(int $itemId, bool $concluido): void
    {
        $stmt = self::pdo()->prepare('UPDATE os_itens SET concluido = :c WHERE id = :id');
        $stmt->execute(['id' => $itemId, 'c' => $concluido ? 1 : 0]);
    }

    public static function adicionarChecklist(int $osId, string $descricao): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO os_checklist (ordem_servico_id, descricao) VALUES (:os, :desc)'
        );
        $stmt->execute(['os' => $osId, 'desc' => $descricao]);
        return (int) self::pdo()->lastInsertId();
    }

    public static function toggleChecklist(int $id, bool $concluido): void
    {
        $stmt = self::pdo()->prepare('UPDATE os_checklist SET concluido = :c WHERE id = :id');
        $stmt->execute(['id' => $id, 'c' => $concluido ? 1 : 0]);
    }

    public static function registrarHoras(int $osId, int $mecanicoId, string $data, float $horas, ?string $desc, ?int $userId): void
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO os_horas (ordem_servico_id, mecanico_id, data_trabalho, horas, descricao, created_by)
             VALUES (:os, :mec, :data, :horas, :desc, :uid)'
        );
        $stmt->execute([
            'os' => $osId,
            'mec' => $mecanicoId,
            'data' => $data,
            'horas' => $horas,
            'desc' => $desc,
            'uid' => $userId,
        ]);
    }

    public static function criarDireta(int $clienteId, int $veiculoId, ?int $userId): int
    {
        $numero = self::proximoNumero();
        $stmt = self::pdo()->prepare(
            'INSERT INTO ordens_servico (numero, cliente_id, veiculo_id, status, created_by)
             VALUES (:numero, :cid, :vid, :status, :uid)'
        );
        $stmt->execute([
            'numero' => $numero,
            'cid' => $clienteId,
            'vid' => $veiculoId,
            'status' => StatusOrdemServico::Aberta->value,
            'uid' => $userId,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $query */
    public static function listar(array $query): array
    {
        $p = self::paginacaoParams($query, ['numero', 'status', 'created_at'], 'created_at');
        $where = '1=1';
        $params = [];

        if ($p['search'] !== '') {
            $where .= ' AND (CAST(os.numero AS CHAR) LIKE :q OR c.nome LIKE :q2 OR v.placa LIKE :q3)';
            $like = '%' . $p['search'] . '%';
            $params = ['q' => $like, 'q2' => $like, 'q3' => $like];
        }
        if (!empty($query['status'])) {
            $where .= ' AND os.status = :status';
            $params['status'] = $query['status'];
        }

        $countStmt = self::pdo()->prepare(
            "SELECT COUNT(*) FROM ordens_servico os
             INNER JOIN clientes c ON c.id = os.cliente_id
             INNER JOIN veiculos v ON v.id = os.veiculo_id WHERE {$where}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = 'SELECT ' . self::COLS . ', c.nome AS cliente_nome, v.placa
                FROM ordens_servico os
                INNER JOIN clientes c ON c.id = os.cliente_id
                INNER JOIN veiculos v ON v.id = os.veiculo_id
                WHERE ' . $where . " ORDER BY os.{$p['sort']} {$p['dir']} LIMIT :limit OFFSET :offset";

        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $p['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue('offset', $p['offset'], \PDO::PARAM_INT);
        $stmt->execute();

        return self::paginacaoResposta($stmt->fetchAll(), $total, $p);
    }
}
