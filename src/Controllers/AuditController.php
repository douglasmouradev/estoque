<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\AuditLog;

final class AuditController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(AuditLog::listar($request->query));
        }
        $this->view('auditoria/index', [
            'titulo' => 'Auditoria',
            'subtitulo' => 'Registro de ações no sistema',
        ]);
    }
}
