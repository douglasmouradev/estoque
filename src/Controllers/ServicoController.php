<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\ServicoCatalogo;

final class ServicoController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(ServicoCatalogo::listar($request->query));
        }
        $this->view('servicos/index', ['titulo' => 'Catálogo de serviços']);
    }

    public function todos(Request $request, array $params): void
    {
        $this->jsonOk(ServicoCatalogo::listarTodos());
    }

    public function store(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $dados = [
            'nome' => $request->string('nome'),
            'descricao' => $request->string('descricao'),
            'preco_padrao' => $request->input('preco_padrao', 0),
        ];
        $v = new Validator($dados);
        $v->required('nome', 'Nome')->decimalPositivo('preco_padrao', 'Preço');
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $id = ServicoCatalogo::criar($dados);
        $this->jsonOk(['id' => $id], 201);
    }

    public function update(Request $request, array $params): void
    {
        $this->exigeEscrita();
        ServicoCatalogo::atualizar((int) $params['id'], [
            'nome' => $request->string('nome'),
            'descricao' => $request->string('descricao'),
            'preco_padrao' => $request->input('preco_padrao', 0),
            'ativo' => $request->bool('ativo', true),
        ]);
        $this->jsonOk();
    }

    public function destroy(Request $request, array $params): void
    {
        $this->exigeEscrita();
        ServicoCatalogo::remover((int) $params['id']);
        $this->jsonOk();
    }
}
