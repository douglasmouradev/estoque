<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\RelatorioService;

final class BuscaController extends Controller
{
    public function global(Request $request, array $params): void
    {
        $this->jsonOk(RelatorioService::buscaGlobal($request->string('q')));
    }
}
