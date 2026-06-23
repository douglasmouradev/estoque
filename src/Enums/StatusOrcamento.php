<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusOrcamento: string
{
    case Rascunho = 'rascunho';
    case Enviado = 'enviado';
    case Aprovado = 'aprovado';
    case Reprovado = 'reprovado';
    case Convertido = 'convertido';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Enviado => 'Aguardando cliente',
            self::Aprovado => 'Aprovado',
            self::Reprovado => 'Reprovado',
            self::Convertido => 'Convertido em OS',
        };
    }
}
