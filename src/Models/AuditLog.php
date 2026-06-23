<?php

declare(strict_types=1);

namespace App\Models;

final class AuditLog extends Model
{
    /** @param array<string, mixed>|null $dados */
    public static function registrar(
        ?int $userId,
        string $acao,
        string $entidade,
        ?int $entidadeId = null,
        ?array $dados = null,
    ): void {
        $stmt = self::pdo()->prepare(
            'INSERT INTO audit_log (user_id, acao, entidade, entidade_id, dados_json)
             VALUES (:uid, :acao, :ent, :eid, :dados)'
        );
        $stmt->execute([
            'uid' => $userId,
            'acao' => $acao,
            'ent' => $entidade,
            'eid' => $entidadeId,
            'dados' => $dados !== null ? json_encode($dados, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
