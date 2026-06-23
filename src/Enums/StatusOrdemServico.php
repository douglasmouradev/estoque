<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusOrdemServico: string
{
    case Aberta = 'aberta';
    case EmAndamento = 'em_andamento';
    case AguardandoPeca = 'aguardando_peca';
    case Finalizada = 'finalizada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Aberta => 'Aberta',
            self::EmAndamento => 'Em andamento',
            self::AguardandoPeca => 'Aguardando peça',
            self::Finalizada => 'Finalizada',
            self::Cancelada => 'Cancelada',
        };
    }
}
