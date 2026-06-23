<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Orcamento;
use PHPUnit\Framework\TestCase;

final class FinanceiroCalcTest extends TestCase
{
    public function testCalcularTotaisMultiplosItens(): void
    {
        $itens = [
            ['quantidade' => 3, 'preco_unitario' => 50, 'desconto_percent' => 0, 'desconto_valor' => 0],
            ['quantidade' => 1, 'preco_unitario' => 100, 'desconto_percent' => 0, 'desconto_valor' => 0],
        ];
        $tot = Orcamento::calcularTotais($itens, 0, 0);
        $this->assertSame(250.0, $tot['total']);
        $this->assertSame(250.0, $tot['subtotal']);
    }
}
