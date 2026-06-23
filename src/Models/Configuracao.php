<?php

declare(strict_types=1);

namespace App\Models;

final class Configuracao extends Model
{
    public static function get(string $chave, ?string $default = null): ?string
    {
        $stmt = self::pdo()->prepare('SELECT valor FROM configuracoes WHERE chave = :chave LIMIT 1');
        $stmt->execute(['chave' => $chave]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (string) $v : $default;
    }

    public static function set(string $chave, string $valor, ?int $userId = null): void
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO configuracoes (chave, valor, updated_by) VALUES (:chave, :valor, :uid)
             ON DUPLICATE KEY UPDATE valor = :valor2, updated_by = :uid2'
        );
        $stmt->execute([
            'chave' => $chave,
            'valor' => $valor,
            'uid' => $userId,
            'valor2' => $valor,
            'uid2' => $userId,
        ]);
    }

    public static function diasPecasParadas(): int
    {
        return (int) (self::get('pecas_paradas_dias', '90') ?? 90);
    }

    /** @return array<string, string> */
    public static function oficina(): array
    {
        return [
            'nome' => self::get('oficina_nome', 'Oficina Mecânica') ?? '',
            'cnpj' => self::get('oficina_cnpj', '') ?? '',
            'telefone' => self::get('oficina_telefone', '') ?? '',
            'email' => self::get('oficina_email', '') ?? '',
            'endereco' => self::get('oficina_endereco', '') ?? '',
        ];
    }

    /** @param array<string, string> $dados */
    public static function salvarOficina(array $dados, ?int $userId): void
    {
        $map = [
            'oficina_nome' => $dados['nome'] ?? '',
            'oficina_cnpj' => $dados['cnpj'] ?? '',
            'oficina_telefone' => $dados['telefone'] ?? '',
            'oficina_email' => $dados['email'] ?? '',
            'oficina_endereco' => $dados['endereco'] ?? '',
        ];
        foreach ($map as $chave => $valor) {
            self::set($chave, $valor, $userId);
        }
    }
}
