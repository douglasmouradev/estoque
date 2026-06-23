<?php

declare(strict_types=1);

namespace App\Models;

final class User extends Model
{
    private const COLS = 'id, nome, email, password_hash, perfil, ativo, must_change_password, created_at, updated_at';

    public static function findById(int $id): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ' FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT ' . self::COLS . ' FROM users WHERE email = :email AND deleted_at IS NULL AND ativo = 1 LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function listarMecanicos(): array
    {
        $stmt = self::pdo()->query(
            "SELECT id, nome, email, perfil FROM users
             WHERE deleted_at IS NULL AND ativo = 1 AND perfil IN ('mecanico', 'gerente', 'admin')
             ORDER BY nome ASC"
        );
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $query */
    public static function listar(array $query = []): array
    {
        $p = self::paginacaoParams($query, ['nome', 'email', 'perfil'], 'nome');
        $where = 'deleted_at IS NULL';
        $params = [];
        if ($p['search'] !== '') {
            $where .= ' AND (nome LIKE :q OR email LIKE :q2)';
            $like = '%' . $p['search'] . '%';
            $params = ['q' => $like, 'q2' => $like];
        }
        $count = self::pdo()->prepare("SELECT COUNT(*) FROM users WHERE {$where}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $sql = "SELECT id, nome, email, perfil, ativo, created_at FROM users WHERE {$where}
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

    /** @param array<string, mixed> $dados */
    public static function criar(array $dados, ?int $userId): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO users (nome, email, password_hash, perfil, ativo, created_by)
             VALUES (:nome, :email, :hash, :perfil, 1, :uid)'
        );
        $stmt->execute([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'hash' => password_hash($dados['senha'], PASSWORD_DEFAULT),
            'perfil' => $dados['perfil'],
            'uid' => $userId,
        ]);
        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $dados */
    public static function atualizar(int $id, array $dados): void
    {
        if (!empty($dados['senha'])) {
            $stmt = self::pdo()->prepare(
                'UPDATE users SET nome = :nome, email = :email, perfil = :perfil, ativo = :ativo,
                 password_hash = :hash WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute([
                'id' => $id,
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'perfil' => $dados['perfil'],
                'ativo' => !empty($dados['ativo']) ? 1 : 0,
                'hash' => password_hash($dados['senha'], PASSWORD_DEFAULT),
            ]);
            return;
        }
        $stmt = self::pdo()->prepare(
            'UPDATE users SET nome = :nome, email = :email, perfil = :perfil, ativo = :ativo
             WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'perfil' => $dados['perfil'],
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
        ]);
    }

    public static function softDelete(int $id): void
    {
        $stmt = self::pdo()->prepare('UPDATE users SET deleted_at = NOW(), ativo = 0 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function marcarSenhaAlterada(int $id): void
    {
        self::pdo()->prepare('UPDATE users SET must_change_password = 0 WHERE id = :id')->execute(['id' => $id]);
    }

    public static function deveTrocarSenha(int $id): bool
    {
        $stmt = self::pdo()->prepare('SELECT must_change_password FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    public static function criarTokenReset(string $email): ?string
    {
        $user = self::findByEmail($email);
        if ($user === null) {
            return null;
        }
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        self::pdo()->prepare('DELETE FROM password_reset_tokens WHERE user_id = :uid')->execute(['uid' => $user['id']]);
        self::pdo()->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 1 HOUR))'
        )->execute(['uid' => $user['id'], 'hash' => $hash]);
        return $token;
    }

    public static function validarTokenReset(string $token): ?int
    {
        $hash = hash('sha256', $token);
        $stmt = self::pdo()->prepare(
            'SELECT user_id FROM password_reset_tokens
             WHERE token_hash = :hash AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['hash' => $hash]);
        $uid = $stmt->fetchColumn();
        return $uid !== false ? (int) $uid : null;
    }

    public static function redefinirSenhaPorToken(string $token, string $senha): bool
    {
        $uid = self::validarTokenReset($token);
        if ($uid === null) {
            return false;
        }
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $pdo = self::pdo();
        $pdo->prepare('UPDATE users SET password_hash = :h, must_change_password = 0 WHERE id = :id')
            ->execute(['h' => $hash, 'id' => $uid]);
        $pdo->prepare(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE token_hash = :hash'
        )->execute(['hash' => hash('sha256', $token)]);
        return true;
    }
}
