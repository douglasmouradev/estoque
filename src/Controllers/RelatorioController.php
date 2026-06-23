<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\RelatorioService;

final class RelatorioController extends Controller
{
    public function index(Request $request, array $params): void
    {
        $de = $request->string('de') ?: null;
        $ate = $request->string('ate') ?: null;
        if ($request->wantsJson()) {
            $this->jsonOk(RelatorioService::dashboard($de, $ate));
        }
        $d = RelatorioService::dashboard($de, $ate);
        $this->view('relatorios/index', [
            'titulo' => 'Relatórios',
            'subtitulo' => 'Visão operacional e financeira',
            'dados' => $d,
            'de' => $de ?? date('Y-m-01'),
            'ate' => $ate ?? date('Y-m-d'),
        ]);
    }

    public function exportar(Request $request, array $params): void
    {
        $tipo = $request->string('tipo') ?: 'estoque';
        $csv = RelatorioService::exportarCsv($tipo, $request->string('de') ?: null, $request->string('ate') ?: null);
        Response::downloadString($csv, 'relatorio_' . $tipo . '_' . date('Y-m-d') . '.csv', 'text/csv; charset=utf-8');
    }
}
