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

    public static function registrarPagamento(int $osId, float $valor, ?int $userId): void
    {
        $os = OrdemServico::findById($osId);
        if ($os === null) {
            throw new \RuntimeException('OS não encontrada.');
        }
        if ($valor <= 0) {
            throw new \InvalidArgumentException('Valor deve ser maior que zero.');
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
        Database::pdo()->prepare(
            'UPDATE ordens_servico SET valor_pago = :p, valor_total = :t, status_pagamento = :st WHERE id = :id'
        )->execute(['p' => $pago, 't' => $total, 'st' => $status, 'id' => $osId]);
        \App\Models\AuditLog::registrar($userId, 'pagamento', 'ordem_servico', $osId, [
            'valor' => $valor,
            'total_pago' => $pago,
        ]);
    }
}
