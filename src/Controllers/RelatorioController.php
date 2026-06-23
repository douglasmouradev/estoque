<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\RelatorioService;

final class RelatorioController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(RelatorioService::dashboard());
        }
        $d = RelatorioService::dashboard();
        $this->view('relatorios/index', [
            'titulo' => 'Relatórios',
            'subtitulo' => 'Visão operacional e financeira',
            'dados' => $d,
        ]);
    }
}
