<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Configuracao;
use App\Models\Peca;
use App\Services\RelatorioService;

final class DashboardController extends Controller
{
    public function index(Request $request, array $params): void
    {
        $rel = RelatorioService::dashboard();
        $this->view('dashboard/index', [
            'titulo' => 'Painel',
            'alertas_estoque' => $rel['pecas_abaixo_minimo'],
            'dias_paradas' => Configuracao::diasPecasParadas(),
            'qtd_paradas' => count($rel['pecas_paradas']),
            'os_abertas' => $rel['os_abertas'],
            'orcamentos_aguardando' => $rel['orcamentos_aguardando'],
            'financeiro_pendente' => $rel['financeiro_pendente'],
        ]);
    }
}
