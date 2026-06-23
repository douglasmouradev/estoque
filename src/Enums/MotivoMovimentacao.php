<?php

declare(strict_types=1);

namespace App\Enums;

enum MotivoMovimentacao: string
{
    case Compra = 'compra';
    case Devolucao = 'devolucao';
    case UsoOs = 'uso_os';
    case Ajuste = 'ajuste';
    case CancelamentoOs = 'cancelamento_os';

    public function label(): string
    {
        return match ($this) {
            self::Compra => 'Compra',
            self::Devolucao => 'Devolução',
            self::UsoOs => 'Uso em OS',
            self::Ajuste => 'Ajuste de estoque',
            self::CancelamentoOs => 'Estorno (OS cancelada)',
        };
    }

    /** Entrada aumenta saldo; saída diminui. */
    public function isEntrada(): bool
    {
        return match ($this) {
            self::Compra, self::Devolucao, self::CancelamentoOs => true,
            self::UsoOs, self::Ajuste => false,
        };
    }
}
