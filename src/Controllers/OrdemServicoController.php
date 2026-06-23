<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Validator;
use App\Enums\PerfilUsuario;
use App\Enums\StatusOrdemServico;
use App\Enums\TipoItemOrcamento;
use App\Models\Configuracao;
use App\Models\OrdemServico;
use App\Models\User;
use App\Services\FinanceiroService;
use App\Services\PdfGenerator;

final class OrdemServicoController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(OrdemServico::listar($request->query));
        }
        $this->view('os/index', ['titulo' => 'Ordens de Serviço']);
    }

    public function store(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $dados = [
            'cliente_id' => $request->int('cliente_id'),
            'veiculo_id' => $request->int('veiculo_id'),
        ];
        $v = new Validator($dados);
        $v->required('cliente_id', 'Cliente')->required('veiculo_id', 'Veículo');
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $id = OrdemServico::criarDireta($dados['cliente_id'], $dados['veiculo_id'], Auth::id());
        $this->jsonOk(['id' => $id], 201);
    }

    public function show(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $os = OrdemServico::findById($id);
        if ($os === null) {
            $this->naoEncontrado($request, 'OS não encontrada');
        }
        if ($request->wantsJson()) {
            FinanceiroService::atualizarTotal($id);
            $os = OrdemServico::findById($id);
            $os['itens'] = OrdemServico::itens($id);
            $os['checklist'] = OrdemServico::checklist($id);
            $os['horas'] = OrdemServico::horas($id);
            $os['mecanicos'] = User::listarMecanicos();
            $os['pode_finalizar'] = Auth::perfil() !== PerfilUsuario::Mecanico;
            $os['pode_editar_itens'] = Auth::perfil() !== PerfilUsuario::Mecanico
                && !in_array($os['status'], ['finalizada', 'cancelada'], true);
            $os['link_portal'] = OrdemServico::linkPortal($id);
            $os['pagamentos'] = FinanceiroService::pagamentosOs($id);
            $this->jsonOk($os);
        }
        $this->view('os/show', ['titulo' => 'OS #' . $os['numero'], 'osId' => $id]);
    }

    public function atualizarStatus(Request $request, array $params): void
    {
        try {
            $status = StatusOrdemServico::from($request->string('status'));
            OrdemServico::atualizarStatus((int) $params['id'], $status, Auth::id());
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk();
    }

    public function adicionarItem(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $tipo = $request->string('tipo', 'servico');
        if (!in_array($tipo, array_column(TipoItemOrcamento::cases(), 'value'), true)) {
            $this->jsonErro('Tipo de item inválido.');
        }
        try {
            $id = OrdemServico::adicionarItem(
                (int) $params['id'],
                $tipo,
                $request->string('descricao'),
                (float) $request->input('quantidade', 1),
                (float) $request->input('preco_unitario', 0),
                $request->int('peca_id') ?: null,
            );
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk(['id' => $id], 201);
    }

    public function removerItem(Request $request, array $params): void
    {
        $this->exigeEscrita();
        try {
            OrdemServico::removerItem((int) $params['itemId']);
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk();
    }

    public function finalizar(Request $request, array $params): void
    {
        if (Auth::perfil() === PerfilUsuario::Mecanico) {
            $this->jsonErro('Mecânico não pode finalizar OS.', 403);
        }
        try {
            OrdemServico::finalizar((int) $params['id'], Auth::id());
            Logger::info('OS finalizada', ['os_id' => (int) $params['id'], 'user' => Auth::id()]);
        } catch (\Throwable $e) {
            Logger::error('Falha ao finalizar OS', ['os_id' => (int) $params['id'], 'erro' => $e->getMessage()]);
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk();
    }

    public function toggleItem(Request $request, array $params): void
    {
        OrdemServico::toggleItem((int) $params['itemId'], $request->bool('concluido'));
        $this->jsonOk();
    }

    public function adicionarChecklist(Request $request, array $params): void
    {
        $desc = trim($request->string('descricao'));
        if ($desc === '') {
            $this->jsonErro('Descrição obrigatória.');
        }
        $id = OrdemServico::adicionarChecklist((int) $params['id'], $desc);
        $this->jsonOk(['id' => $id], 201);
    }

    public function toggleChecklist(Request $request, array $params): void
    {
        OrdemServico::toggleChecklist((int) $params['itemId'], $request->bool('concluido'));
        $this->jsonOk();
    }

    public function registrarHoras(Request $request, array $params): void
    {
        $horas = (float) $request->input('horas', 0);
        if ($horas <= 0) {
            $this->jsonErro('Horas devem ser maiores que zero.');
        }
        OrdemServico::registrarHoras(
            (int) $params['id'],
            $request->int('mecanico_id'),
            $request->string('data_trabalho'),
            $horas,
            $request->string('descricao') ?: null,
            Auth::id(),
        );
        $this->jsonOk();
    }

    public function registrarPagamento(Request $request, array $params): void
    {
        $this->exigeEscrita();
        try {
            FinanceiroService::registrarPagamento(
                (int) $params['id'],
                (float) $request->input('valor', 0),
                Auth::id(),
                $request->string('forma_pagamento') ?: 'dinheiro',
                $request->string('observacao') ?: null,
            );
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk();
    }

    public function pdf(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $os = OrdemServico::findById($id);
        if ($os === null) {
            $this->jsonErro('OS não encontrada', 404);
        }
        $itens = OrdemServico::itens($id);
        $path = dirname(__DIR__, 2) . '/storage/pdfs/os_' . $id . '.pdf';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        PdfGenerator::ordemServico($os, $itens, Configuracao::oficina(), $path);
        \App\Core\Response::download($path, 'os_' . $os['numero'] . '.pdf', 'application/pdf');
    }
}
