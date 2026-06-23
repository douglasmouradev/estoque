<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Enums\StatusOrcamento;
use App\Enums\TipoItemOrcamento;
use App\Models\Configuracao;
use App\Models\Orcamento;
use App\Services\OrcamentoService;
use App\Services\PdfGenerator;

final class OrcamentoController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(Orcamento::listar($request->query));
        }
        $this->view('orcamentos/index', ['titulo' => 'Orçamentos']);
    }

    public function show(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $orc = Orcamento::findById($id);
        if ($orc === null) {
            $this->naoEncontrado($request, 'Orçamento não encontrado');
        }
        if ($request->wantsJson()) {
            $orc['itens'] = Orcamento::itens($id);
            $orc['totais'] = Orcamento::calcularTotais(
                $orc['itens'],
                (float) $orc['desconto_geral_percent'],
                (float) $orc['desconto_geral_valor']
            );
            $orc['versoes'] = Orcamento::versoes($id);
            $orc['link_portal'] = OrcamentoService::linkPortal($id);
            $this->jsonOk($orc);
        }
        $this->view('orcamentos/show', ['titulo' => 'Orçamento #' . $orc['numero'], 'orcamentoId' => $id]);
    }

    public function store(Request $request, array $params): void
    {
        $cab = $this->cabecalho($request);
        $v = $this->validarCabecalho($cab);
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $id = Orcamento::criar($cab, Auth::id());
        $itens = $this->normalizarItens($request->body['itens'] ?? []);
        if ($itens !== []) {
            Orcamento::salvarComVersionamento($id, $cab, $itens);
        }
        $this->jsonOk(['id' => $id], 201);
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $orc = Orcamento::findById($id);
        if ($orc === null) {
            $this->jsonErro('Orçamento não encontrado', 404);
        }
        if (in_array($orc['status'], [StatusOrcamento::Convertido->value], true)) {
            $this->jsonErro('Orçamento convertido não pode ser editado.', 400);
        }
        $cab = $this->cabecalho($request);
        $v = $this->validarCabecalho($cab);
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $itens = $this->normalizarItens($request->body['itens'] ?? []);
        Orcamento::salvarComVersionamento($id, $cab, $itens);
        $this->jsonOk(['id' => $id]);
    }

    public function enviar(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        try {
            OrcamentoService::enviar($id, Auth::id());
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk(['link_portal' => OrcamentoService::linkPortal($id)]);
    }

    public function aprovar(Request $request, array $params): void
    {
        try {
            OrcamentoService::aprovar(
                (int) $params['id'],
                Auth::id(),
                $request->string('observacao_cliente') ?: null
            );
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk();
    }

    public function reprovar(Request $request, array $params): void
    {
        OrcamentoService::reprovar(
            (int) $params['id'],
            $request->string('observacao_cliente') ?: null,
            Auth::id()
        );
        $this->jsonOk();
    }

    public function converterOs(Request $request, array $params): void
    {
        try {
            $osId = OrcamentoService::converterOs((int) $params['id'], Auth::id());
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk(['ordem_servico_id' => $osId], 201);
    }

    public function pdf(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $orc = Orcamento::findById($id);
        if ($orc === null) {
            $this->jsonErro('Orçamento não encontrado', 404);
        }
        $itens = Orcamento::itens($id);
        $totais = Orcamento::calcularTotais(
            $itens,
            (float) $orc['desconto_geral_percent'],
            (float) $orc['desconto_geral_valor']
        );
        $path = dirname(__DIR__, 2) . '/storage/pdfs/orcamento_' . $id . '.pdf';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        PdfGenerator::orcamento($orc, $itens, $totais, Configuracao::oficina(), $path);
        \App\Core\Response::download($path, 'orcamento_' . $orc['numero'] . '.pdf', 'application/pdf');
    }

    /** @return array<string, mixed> */
    private function cabecalho(Request $request): array
    {
        return [
            'cliente_id' => $request->int('cliente_id'),
            'veiculo_id' => $request->int('veiculo_id'),
            'desconto_geral_percent' => (float) $request->input('desconto_geral_percent', 0),
            'desconto_geral_valor' => (float) $request->input('desconto_geral_valor', 0),
            'observacao_interna' => $request->string('observacao_interna') ?: null,
        ];
    }

    private function validarCabecalho(array $cab): Validator
    {
        $v = new Validator($cab);
        $v->required('cliente_id', 'Cliente')->required('veiculo_id', 'Veículo');
        return $v;
    }

    /** @param mixed $raw */
    private function normalizarItens(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $itens = [];
        foreach ($raw as $item) {
            if (!is_array($item) || empty($item['descricao'])) {
                continue;
            }
            $tipo = (string) ($item['tipo'] ?? TipoItemOrcamento::Servico->value);
            if (!in_array($tipo, [TipoItemOrcamento::Peca->value, TipoItemOrcamento::Servico->value], true)) {
                continue;
            }
            $itens[] = [
                'tipo' => $tipo,
                'peca_id' => !empty($item['peca_id']) ? (int) $item['peca_id'] : null,
                'descricao' => (string) $item['descricao'],
                'quantidade' => max(0.001, (float) ($item['quantidade'] ?? 1)),
                'preco_unitario' => max(0, (float) ($item['preco_unitario'] ?? 0)),
                'desconto_percent' => max(0, (float) ($item['desconto_percent'] ?? 0)),
                'desconto_valor' => max(0, (float) ($item['desconto_valor'] ?? 0)),
            ];
        }
        return $itens;
    }
}
