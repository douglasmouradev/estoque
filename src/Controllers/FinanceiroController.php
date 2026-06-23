<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\FinanceiroService;
use App\Services\RelatorioService;

final class FinanceiroController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(FinanceiroService::contasReceber($request->query));
        }
        $this->view('financeiro/index', [
            'titulo' => 'Financeiro',
            'subtitulo' => 'Contas a receber e pagamentos',
        ]);
    }

    public function exportar(Request $request, array $params): void
    {
        $csv = FinanceiroService::exportarCsv($request->query);
        Response::downloadString($csv, 'financeiro_' . date('Y-m-d') . '.csv', 'text/csv; charset=utf-8');
    }
}
