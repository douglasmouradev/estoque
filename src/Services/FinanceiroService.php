<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\OrdemServico;

final class FinanceiroService
{
    public static function calcularTotalOs(int $osId): float
    {
        $total = 0.0;
        foreach (OrdemServico::itens($osId) as $item) {
            $total += (float) $item['quantidade'] * (float) $item['preco_unitario'];
        }
        return round($total, 2);
    }

    public static function atualizarTotal(int $osId): void
    {
        $total = self::calcularTotalOs($osId);
        Database::pdo()->prepare(
            'UPDATE ordens_servico SET valor_total = :t WHERE id = :id'
        )->execute(['t' => $total, 'id' => $osId]);
    }

    public static function registrarPagamento(
        int $osId,
        float $valor,
        ?int $userId,
        string $formaPagamento = 'dinheiro',
        ?string $observacao = null,
    ): void {
        $os = OrdemServico::findById($osId);
        if ($os === null) {
            throw new \RuntimeException('OS não encontrada.');
        }
        if ($valor <= 0) {
            throw new \InvalidArgumentException('Valor deve ser maior que zero.');
        }
        $formas = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'transferencia', 'outro'];
        if (!in_array($formaPagamento, $formas, true)) {
            $formaPagamento = 'dinheiro';
        }
        $pago = round((float) $os['valor_pago'] + $valor, 2);
        $total = (float) $os['valor_total'];
        if ($total <= 0) {
            $total = self::calcularTotalOs($osId);
        }
        $status = 'parcial';
        if ($pago >= $total) {
            $pago = $total;
            $status = 'pago';
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            'UPDATE ordens_servico SET valor_pago = :p, valor_total = :t, status_pagamento = :st WHERE id = :id'
        )->execute(['p' => $pago, 't' => $total, 'st' => $status, 'id' => $osId]);
        $pdo->prepare(
            'INSERT INTO os_pagamentos (ordem_servico_id, valor, forma_pagamento, observacao, created_by)
             VALUES (:os, :v, :f, :obs, :uid)'
        )->execute([
            'os' => $osId,
            'v' => $valor,
            'f' => $formaPagamento,
            'obs' => $observacao,
            'uid' => $userId,
        ]);
        \App\Models\AuditLog::registrar($userId, 'pagamento', 'ordem_servico', $osId, [
            'valor' => $valor,
            'forma' => $formaPagamento,
            'total_pago' => $pago,
        ]);
    }

    /** @param array<string, mixed> $query */
    public static function contasReceber(array $query): array
    {
        $pdo = Database::pdo();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 20)));
        $offset = ($page - 1) * $perPage;

        $where = "os.status = 'finalizada' AND os.status_pagamento != 'pago'";
        $count = (int) $pdo->query("SELECT COUNT(*) FROM ordens_servico os WHERE {$where}")->fetchColumn();

        $stmt = $pdo->prepare(
            "SELECT os.id, os.numero, os.valor_total, os.valor_pago, os.status_pagamento,
                    c.nome AS cliente_nome, v.placa, os.finalizada_em
             FROM ordens_servico os
             INNER JOIN clientes c ON c.id = os.cliente_id
             INNER JOIN veiculos v ON v.id = os.veiculo_id
             WHERE {$where}
             ORDER BY os.finalizada_em DESC LIMIT :lim OFFSET :off"
        );
        $stmt->bindValue('lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $itens = $stmt->fetchAll();
        foreach ($itens as &$row) {
            $row['saldo'] = round((float) $row['valor_total'] - (float) $row['valor_pago'], 2);
        }
        unset($row);

        $pendente = (float) $pdo->query(
            "SELECT COALESCE(SUM(valor_total - valor_pago), 0) FROM ordens_servico
             WHERE status = 'finalizada' AND status_pagamento != 'pago'"
        )->fetchColumn();

        return [
            'itens' => $itens,
            'total' => $count,
            'page' => $page,
            'per_page' => $perPage,
            'total_pendente' => round($pendente, 2),
        ];
    }

    /** @param array<string, mixed> $query */
    public static function exportarCsv(array $query): string
    {
        $dados = self::contasReceber(array_merge($query, ['per_page' => 1000, 'page' => 1]));
        $out = "OS;Cliente;Placa;Total;Pago;Saldo;Status;Finalizada\n";
        foreach ($dados['itens'] as $r) {
            $out .= implode(';', [
                $r['numero'],
                str_replace(';', ',', (string) $r['cliente_nome']),
                $r['placa'],
                number_format((float) $r['valor_total'], 2, ',', ''),
                number_format((float) $r['valor_pago'], 2, ',', ''),
                number_format((float) $r['saldo'], 2, ',', ''),
                $r['status_pagamento'],
                $r['finalizada_em'] ?? '',
            ]) . "\n";
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function pagamentosOs(int $osId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT p.*, u.nome AS user_nome FROM os_pagamentos p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.ordem_servico_id = :id ORDER BY p.created_at DESC'
        );
        $stmt->execute(['id' => $osId]);
        return $stmt->fetchAll();
    }
}
