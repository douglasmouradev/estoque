<?php

declare(strict_types=1);

namespace App\Enums;

enum PerfilUsuario: string
{
    case Admin = 'admin';
    case Gerente = 'gerente';
    case Mecanico = 'mecanico';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Gerente => 'Gerente',
            self::Mecanico => 'Mecânico',
        };
    }

    /** Rotas prefixadas que o perfil pode acessar (vazio = sem restrição extra além do middleware). */
    public function rotasBloqueadas(): array
    {
        return match ($this) {
            self::Admin => [],
            self::Gerente => ['/config'],
            self::Mecanico => ['/config', '/orcamentos', '/clientes', '/relatorios', '/auditoria', '/financeiro'],
        };
    }
}
