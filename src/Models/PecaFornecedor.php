<?php

declare(strict_types=1);

namespace App\Models;

final class PecaFornecedor extends Model
{
    public static function listarPorPeca(int $pecaId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT pf.id, pf.peca_id, pf.fornecedor_id, pf.preco_compra, pf.prazo_entrega_dias, pf.preferencial,
                    f.razao_social, f.nome_fantasia
             FROM peca_fornecedor pf
             INNER JOIN fornecedores f ON f.id = pf.fornecedor_id AND f.deleted_at IS NULL
             WHERE pf.peca_id = :peca_id
             ORDER BY pf.preferencial DESC, f.razao_social ASC'
        );
        $stmt->execute(['peca_id' => $pecaId]);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $dados */
    public static function salvar(int $pecaId, array $dados): void
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO peca_fornecedor (peca_id, fornecedor_id, preco_compra, prazo_entrega_dias, preferencial)
             VALUES (:peca_id, :fornecedor_id, :preco, :prazo, :pref)
             ON DUPLICATE KEY UPDATE preco_compra = :preco2, prazo_entrega_dias = :prazo2, preferencial = :pref2'
        );
        $stmt->execute([
            'peca_id' => $pecaId,
            'fornecedor_id' => $dados['fornecedor_id'],
            'preco' => $dados['preco_compra'],
            'prazo' => $dados['prazo_entrega_dias'] ?? 0,
            'pref' => !empty($dados['preferencial']) ? 1 : 0,
            'preco2' => $dados['preco_compra'],
            'prazo2' => $dados['prazo_entrega_dias'] ?? 0,
            'pref2' => !empty($dados['preferencial']) ? 1 : 0,
        ]);
    }

    public static function remover(int $id): void
    {
        $stmt = self::pdo()->prepare('DELETE FROM peca_fornecedor WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
