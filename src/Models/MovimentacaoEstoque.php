<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Enums\MotivoMovimentacao;
use PDO;

final class MovimentacaoEstoque extends Model
{
    /**
     * Registra movimentação com validação de saldo em saídas.
     * Transação obrigatória quando chamado de fora de outra transação.
     */
    public static function registrar(
        int $pecaId,
        MotivoMovimentacao $motivo,
        float $quantidade,
        ?int $osId = null,
        ?string $observacao = null,
        ?int $userId = null,
    ): int {
        if ($quantidade <= 0) {
            throw new \InvalidArgumentException('Quantidade deve ser maior que zero.');
        }

        $tipo = $motivo->isEntrada() ? 'entrada' : 'saida';

        if ($tipo === 'saida') {
            $saldo = Peca::saldoAtual($pecaId);
            if ($saldo < $quantidade) {
                throw new \RuntimeException('Saldo insuficiente para esta saída.');
            }
        }

        $stmt = self::pdo()->prepare(
            'INSERT INTO movimentacoes_estoque (peca_id, tipo, quantidade, motivo, ordem_servico_id, observacao, created_by)
             VALUES (:peca_id, :tipo, :quantidade, :motivo, :os_id, :observacao, :created_by)'
        );
        $stmt->execute([
            'peca_id' => $pecaId,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'motivo' => $motivo->value,
            'os_id' => $osId,
            'observacao' => $observacao,
            'created_by' => $userId,
        ]);

        return (int) self::pdo()->lastInsertId();
    }

    public static function emTransacao(callable $fn): mixed
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
