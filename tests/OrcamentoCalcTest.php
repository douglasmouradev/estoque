<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Orcamento;
use PHPUnit\Framework\TestCase;

final class OrcamentoCalcTest extends TestCase
{
    public function testCalcularTotaisComDesconto(): void
    {
        $itens = [
            ['quantidade' => 2, 'preco_unitario' => 100, 'desconto_percent' => 0, 'desconto_valor' => 0],
            ['quantidade' => 1, 'preco_unitario' => 50, 'desconto_percent' => 0, 'desconto_valor' => 0],
        ];
        $tot = Orcamento::calcularTotais($itens, 10, 0);
        $this->assertSame(250.0, $tot['subtotal']);
        $this->assertSame(25.0, $tot['desconto_geral']);
        $this->assertSame(225.0, $tot['total']);
    }
}
