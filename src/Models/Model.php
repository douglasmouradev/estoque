<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected static function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array{page: int, per_page: int, sort: string, dir: string, search: string} */
    protected static function paginacaoParams(array $query, array $sortAllowed, string $defaultSort = 'id'): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(5, (int) ($query['per_page'] ?? 20)));
        $sort = (string) ($query['sort'] ?? $defaultSort);
        if (!in_array($sort, $sortAllowed, true)) {
            $sort = $defaultSort;
        }
        $dir = strtoupper((string) ($query['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $search = trim((string) ($query['q'] ?? ''));

        return [
            'page' => $page,
            'per_page' => $perPage,
            'sort' => $sort,
            'dir' => $dir,
            'search' => $search,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    /** @param array<string, mixed> $meta */
    protected static function paginacaoResposta(array $rows, int $total, array $meta): array
    {
        return [
            'itens' => $rows,
            'total' => $total,
            'page' => $meta['page'],
            'per_page' => $meta['per_page'],
            'total_pages' => (int) ceil($total / max(1, $meta['per_page'])),
        ];
    }
}
