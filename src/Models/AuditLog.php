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

    /** @param array<string, mixed> $query */
    public static function listar(array $query = []): array
    {
        $p = self::paginacaoParams($query, ['created_at', 'acao', 'entidade'], 'created_at');
        $where = '1=1';
        $params = [];
        if (!empty($query['entidade'])) {
            $where .= ' AND a.entidade = :ent';
            $params['ent'] = $query['entidade'];
        }
        if (!empty($query['acao'])) {
            $where .= ' AND a.acao = :acao';
            $params['acao'] = $query['acao'];
        }
        $count = self::pdo()->prepare("SELECT COUNT(*) FROM audit_log a WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT a.id, a.user_id, u.nome AS user_nome, a.acao, a.entidade, a.entidade_id,
                a.dados_json, a.created_at
                FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
                WHERE {$where} ORDER BY a.{$p['sort']} {$p['dir']} LIMIT :limit OFFSET :offset";
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
