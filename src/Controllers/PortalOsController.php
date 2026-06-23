<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\IpRateLimiter;
use App\Core\Request;
use App\Models\OrdemServico;

final class PortalOsController extends Controller
{
    public function show(Request $request, array $params): void
    {
        if (IpRateLimiter::tooMany('portal_os_' . ($params['token'] ?? ''), 60, 3600)) {
            http_response_code(429);
            echo 'Muitas requisições. Tente mais tarde.';
            return;
        }
        $os = OrdemServico::findByToken($params['token'] ?? '');
        if ($os === null) {
            $this->view('portal/os-erro', ['titulo' => 'Link inválido'], null);
            return;
        }
        $os['itens'] = OrdemServico::itens((int) $os['id']);
        $this->view('portal/os', [
            'titulo' => 'OS #' . $os['numero'],
            'os' => $os,
            'token' => $params['token'],
        ], null);
    }
}
