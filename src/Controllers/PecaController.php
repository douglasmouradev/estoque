<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Validator;
use App\Enums\MotivoMovimentacao;
use App\Enums\UnidadePeca;
use App\Models\Configuracao;
use App\Models\MovimentacaoEstoque;
use App\Models\Peca;
use App\Models\PecaFornecedor;
use App\Services\CsvPecaImporter;

final class PecaController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(Peca::listar($request->query));
        }
        $this->view('estoque/index', ['titulo' => 'Estoque']);
    }

    public function show(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $peca = Peca::findById($id);
        if ($peca === null) {
            $this->naoEncontrado($request, 'Peça não encontrada');
        }
        if ($request->wantsJson()) {
            $peca['fornecedores'] = PecaFornecedor::listarPorPeca($id);
            $peca['historico'] = Peca::historicoMovimentacoes(
                $id,
                $request->int('page', 1),
                $request->int('per_page', 50)
            );
            $this->jsonOk($peca);
        }
        $this->view('estoque/show', ['titulo' => $peca['descricao'], 'peca' => $peca]);
    }

    public function store(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $dados = $this->extrairDados($request);
        $v = $this->validar($dados, true);
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        try {
            $id = Peca::criar($dados, Auth::id());
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk(['id' => $id], 201);
    }

    public function update(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $dados = $this->extrairDados($request);
        $v = $this->validar($dados);
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        Peca::atualizar((int) $params['id'], $dados);
        $this->jsonOk();
    }

    public function destroy(Request $request, array $params): void
    {
        $this->exigeEscrita();
        Peca::softDelete((int) $params['id']);
        $this->jsonOk();
    }

    public function movimentar(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $pecaId = (int) $params['id'];
        $motivo = MotivoMovimentacao::from($request->string('motivo'));
        $quantidade = (float) $request->input('quantidade', 0);
        $obs = $request->string('observacao') ?: null;

        try {
            MovimentacaoEstoque::emTransacao(function () use ($pecaId, $motivo, $quantidade, $obs) {
                MovimentacaoEstoque::registrar($pecaId, $motivo, $quantidade, null, $obs, Auth::id());
            });
        } catch (\Throwable $e) {
            $this->jsonErro($e->getMessage(), 400);
        }
        $this->jsonOk(['saldo' => Peca::saldoAtual($pecaId)]);
    }

    public function autocomplete(Request $request, array $params): void
    {
        $q = $request->string('q');
        $this->jsonOk(strlen($q) >= 1 ? Peca::buscarAutocomplete($q) : []);
    }

    public function paradas(Request $request, array $params): void
    {
        $dias = $request->int('dias') ?: Configuracao::diasPecasParadas();
        if ($request->wantsJson()) {
            $this->jsonOk(Peca::paradas($dias));
        }
        $this->view('estoque/paradas', [
            'titulo' => 'Peças paradas',
            'dias' => $dias,
            'pecas' => Peca::paradas($dias),
        ]);
    }

    public function importarCsv(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $file = $request->files['arquivo'] ?? null;
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->jsonErro('Envie um arquivo CSV válido.');
        }
        $maxBytes = ((int) (App::config('app')['upload_max_mb'] ?? 5)) * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            $this->jsonErro('Arquivo excede o tamanho máximo permitido.');
        }
        $nome = (string) ($file['name'] ?? '');
        if (!str_ends_with(strtolower($nome), '.csv')) {
            $this->jsonErro('Envie um arquivo com extensão .csv');
        }
        $dir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = $dir . '/' . uniqid('csv_', true) . '.csv';
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->jsonErro('Falha ao salvar upload.');
        }
        $resultado = CsvPecaImporter::importar($dest, Auth::id());
        unlink($dest);
        Logger::info('Importação CSV', [
            'importados' => $resultado['importados'],
            'erros' => count($resultado['erros']),
            'user' => Auth::id(),
        ]);
        $this->jsonOk($resultado);
    }

    public function salvarFornecedor(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $pecaId = (int) $params['id'];
        PecaFornecedor::salvar($pecaId, [
            'fornecedor_id' => $request->int('fornecedor_id'),
            'preco_compra' => $request->input('preco_compra'),
            'prazo_entrega_dias' => $request->int('prazo_entrega_dias'),
            'preferencial' => $request->bool('preferencial'),
        ]);
        $this->jsonOk();
    }

    /** @return array<string, mixed> */
    private function extrairDados(Request $request): array
    {
        return [
            'codigo_interno' => $request->string('codigo_interno'),
            'codigo_oem' => $request->string('codigo_oem') ?: null,
            'descricao' => $request->string('descricao'),
            'unidade' => $request->string('unidade', 'un'),
            'categoria_id' => $request->int('categoria_id') ?: null,
            'marca' => $request->string('marca') ?: null,
            'localizacao' => $request->string('localizacao') ?: null,
            'estoque_minimo' => $request->input('estoque_minimo', 0),
            'preco_venda' => $request->input('preco_venda', 0),
            'estoque_inicial' => $request->input('estoque_inicial', 0),
        ];
    }

    private function validar(array $dados, bool $criacao = false): Validator
    {
        $v = new Validator($dados);
        $v->required('codigo_interno', 'Código interno')
            ->required('descricao', 'Descrição')
            ->inEnum('unidade', array_column(UnidadePeca::cases(), 'value'), 'Unidade')
            ->decimalPositivo('preco_venda', 'Preço de venda')
            ->decimalPositivo('estoque_minimo', 'Estoque mínimo');
        if ($criacao) {
            $v->decimalPositivo('estoque_inicial', 'Saldo inicial');
        }
        return $v;
    }
}
