<?php

declare(strict_types=1);

namespace App\Models;

final class ServicoCatalogo extends Model
{
    public static function listarTodos(): array
    {
        $stmt = self::pdo()->query(
            'SELECT id, nome, descricao, preco_padrao, ativo FROM servicos_catalogo
             WHERE ativo = 1 ORDER BY nome ASC'
        );
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $query */
    public static function listar(array $query): array
    {
        $p = self::paginacaoParams($query, ['nome', 'preco_padrao'], 'nome');
        $where = '1=1';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND nome LIKE :q';
            $params['q'] = '%' . $p['search'] . '%';
        }
        $count = self::pdo()->prepare("SELECT COUNT(*) FROM servicos_catalogo WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT id, nome, descricao, preco_padrao, ativo, created_at
                FROM servicos_catalogo WHERE {$where}
                ORDER BY {$p['sort']} {$p['dir']} LIMIT :limit OFFSET :offset";
        $stmt = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $p['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue('offset', $p['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        return self::paginacaoResposta($stmt->fetchAll(), $total, $p);
    }

    public static function criar(array $dados): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO servicos_catalogo (nome, descricao, preco_padrao) VALUES (:nome, :desc, :preco)'
        );
        $stmt->execute([
            'nome' => $dados['nome'],
            'desc' => $dados['descricao'] ?? null,
            'preco' => $dados['preco_padrao'] ?? 0,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    public static function atualizar(int $id, array $dados): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE servicos_catalogo SET nome = :nome, descricao = :desc,
             preco_padrao = :preco, ativo = :ativo WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'desc' => $dados['descricao'] ?? null,
            'preco' => $dados['preco_padrao'] ?? 0,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
        ]);
    }

    public static function remover(int $id): void
    {
        self::pdo()->prepare('UPDATE servicos_catalogo SET ativo = 0 WHERE id = :id')->execute(['id' => $id]);
    }
}
