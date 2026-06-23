<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Cliente;
use App\Models\Veiculo;

final class ClienteController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk(Cliente::listar($request->query));
        }
        $this->view('clientes/index', ['titulo' => 'Clientes']);
    }

    public function show(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $cliente = Cliente::findById($id);
        if ($cliente === null) {
            $this->naoEncontrado($request, 'Cliente não encontrado');
        }
        if ($request->wantsJson()) {
            $cliente['veiculos'] = Veiculo::listarPorCliente($id, $request->query);
            $this->jsonOk($cliente);
        }
        $this->view('clientes/show', ['titulo' => $cliente['nome'], 'cliente' => $cliente]);
    }

    public function store(Request $request, array $params): void
    {
        $dados = $this->extrairDados($request);
        $v = $this->validar($dados);
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $id = Cliente::criar($dados, Auth::id());
        $this->jsonOk(['id' => $id], 201);
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $dados = $this->extrairDados($request);
        $v = $this->validar($dados);
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        Cliente::atualizar($id, $dados);
        $this->jsonOk(['id' => $id]);
    }

    public function destroy(Request $request, array $params): void
    {
        Cliente::softDelete((int) $params['id']);
        $this->jsonOk();
    }

    public function buscar(Request $request, array $params): void
    {
        $q = $request->string('q');
        if (strlen($q) < 2) {
            $this->jsonOk([]);
        }
        $this->jsonOk(Cliente::buscar($q));
    }

    /** @return array<string, mixed> */
    private function extrairDados(Request $request): array
    {
        return [
            'nome' => $request->string('nome'),
            'cpf_cnpj' => preg_replace('/\D/', '', $request->string('cpf_cnpj')),
            'telefone' => $request->string('telefone') ?: null,
            'email' => $request->string('email') ?: null,
            'logradouro' => $request->string('logradouro') ?: null,
            'numero' => $request->string('numero') ?: null,
            'complemento' => $request->string('complemento') ?: null,
            'bairro' => $request->string('bairro') ?: null,
            'cidade' => $request->string('cidade') ?: null,
            'uf' => $request->string('uf') ?: null,
            'cep' => $request->string('cep') ?: null,
        ];
    }

    private function validar(array $dados): Validator
    {
        $v = new Validator($dados);
        $v->required('nome', 'Nome')->required('cpf_cnpj', 'CPF/CNPJ')->cpfCnpj('cpf_cnpj', 'CPF/CNPJ');
        if (!empty($dados['email'])) {
            $v->email('email', 'E-mail');
        }
        return $v;
    }
}
