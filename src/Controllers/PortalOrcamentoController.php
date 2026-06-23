<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Enums\StatusOrcamento;
use App\Services\OrcamentoService;

final class PortalOrcamentoController extends Controller
{
    public function show(Request $request, array $params): void
    {
        $orc = OrcamentoService::porToken($params['token'] ?? '');
        if ($orc === null) {
            $this->view('portal/orcamento-erro', ['titulo' => 'Link inválido'], null);
            return;
        }
        $this->view('portal/orcamento', [
            'titulo' => 'Orçamento #' . $orc['numero'],
            'orcamento' => $orc,
            'token' => $params['token'],
        ], null);
    }

    public function aprovar(Request $request, array $params): void
    {
        $orc = OrcamentoService::porToken($params['token'] ?? '');
        if ($orc === null || $orc['status'] !== StatusOrcamento::Enviado->value) {
            $this->jsonErro('Orçamento indisponível para aprovação.', 400);
        }
        try {
            OrcamentoService::aprovar((int) $orc['id'], null, $request->string('observacao') ?: null, true);
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk(['mensagem' => 'Orçamento aprovado com sucesso!']);
    }

    public function reprovar(Request $request, array $params): void
    {
        $orc = OrcamentoService::porToken($params['token'] ?? '');
        if ($orc === null || $orc['status'] !== StatusOrcamento::Enviado->value) {
            $this->jsonErro('Orçamento indisponível.', 400);
        }
        OrcamentoService::reprovar((int) $orc['id'], $request->string('observacao') ?: null, null);
        $this->jsonOk(['mensagem' => 'Orçamento reprovado.']);
    }
}
