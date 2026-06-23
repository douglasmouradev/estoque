<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ReservaEstoque extends Model
{
    public static function reservadoAtivo(int $pecaId): float
    {
        $stmt = self::pdo()->prepare(
            "SELECT COALESCE(SUM(quantidade), 0) FROM estoque_reservas
             WHERE peca_id = :id AND status = 'ativa'"
        );
        $stmt->execute(['id' => $pecaId]);
        return (float) $stmt->fetchColumn();
    }

    public static function saldoDisponivel(int $pecaId): float
    {
        return Peca::saldoAtual($pecaId) - self::reservadoAtivo($pecaId);
    }

    public static function criarReservasOrcamento(int $orcamentoId): void
    {
        $itens = Orcamento::itens($orcamentoId);
        $pdo = Database::pdo();
        $ins = $pdo->prepare(
            "INSERT INTO estoque_reservas (peca_id, orcamento_id, quantidade, status)
             VALUES (:peca, :orc, :qtd, 'ativa')"
        );
        foreach ($itens as $item) {
            if ($item['tipo'] !== 'peca' || empty($item['peca_id'])) {
                continue;
            }
            $pecaId = (int) $item['peca_id'];
            $qtd = (float) $item['quantidade'];
            if (self::saldoDisponivel($pecaId) < $qtd) {
                throw new \RuntimeException(
                    "Saldo disponível insuficiente para reservar: {$item['descricao']}"
                );
            }
            $ins->execute(['peca' => $pecaId, 'orc' => $orcamentoId, 'qtd' => $qtd]);
        }
    }

    public static function liberarPorOrcamento(int $orcamentoId): void
    {
        self::pdo()->prepare(
            "UPDATE estoque_reservas SET status = 'liberada'
             WHERE orcamento_id = :id AND status = 'ativa'"
        )->execute(['id' => $orcamentoId]);
    }

    public static function consumirPorOrcamento(int $orcamentoId): void
    {
        self::pdo()->prepare(
            "UPDATE estoque_reservas SET status = 'consumida'
             WHERE orcamento_id = :id AND status = 'ativa'"
        )->execute(['id' => $orcamentoId]);
    }
}
