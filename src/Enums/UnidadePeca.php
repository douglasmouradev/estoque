<?php

declare(strict_types=1);

namespace App\Enums;

enum UnidadePeca: string
{
    case Un = 'un';
    case Lt = 'lt';
    case Kg = 'kg';
    case M = 'm';

    public function label(): string
    {
        return match ($this) {
            self::Un => 'Unidade',
            self::Lt => 'Litro',
            self::Kg => 'Quilograma',
            self::M => 'Metro',
        };
    }
}
