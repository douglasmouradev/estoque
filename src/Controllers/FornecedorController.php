<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Fornecedor;

final class FornecedorController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(Fornecedor::listar($request->query));
        }
        $this->view('fornecedores/index', ['titulo' => 'Fornecedores']);
    }

    public function listarTodos(Request $request, array $params): void
    {
        $this->jsonOk(Fornecedor::listarTodos());
    }

    public function store(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $dados = [
            'razao_social' => $request->string('razao_social'),
            'nome_fantasia' => $request->string('nome_fantasia') ?: null,
            'cnpj' => $request->string('cnpj') ?: null,
            'telefone' => $request->string('telefone') ?: null,
            'email' => $request->string('email') ?: null,
        ];
        $v = new Validator($dados);
        $v->required('razao_social', 'Razão social');
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $id = Fornecedor::criar($dados, Auth::id());
        $this->jsonOk(['id' => $id], 201);
    }

    public function update(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $dados = [
            'razao_social' => $request->string('razao_social'),
            'nome_fantasia' => $request->string('nome_fantasia') ?: null,
            'cnpj' => $request->string('cnpj') ?: null,
            'telefone' => $request->string('telefone') ?: null,
            'email' => $request->string('email') ?: null,
        ];
        Fornecedor::atualizar((int) $params['id'], $dados);
        $this->jsonOk();
    }

    public function destroy(Request $request, array $params): void
    {
        $this->exigeEscrita();
        Fornecedor::softDelete((int) $params['id']);
        $this->jsonOk();
    }
}
