<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoItemOrcamento: string
{
    case Peca = 'peca';
    case Servico = 'servico';
}
