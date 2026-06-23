<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Veiculo;
use App\Services\RelatorioService;

final class VeiculoController extends Controller
{
    public function store(Request $request, array $params): void
    {
        $dados = [
            'cliente_id' => $request->int('cliente_id'),
            'placa' => $request->string('placa'),
            'chassi' => $request->string('chassi') ?: null,
            'marca' => $request->string('marca'),
            'modelo' => $request->string('modelo'),
            'ano' => $request->int('ano') ?: null,
            'cor' => $request->string('cor') ?: null,
            'km_atual' => $request->int('km_atual'),
        ];
        $v = new Validator($dados);
        $v->required('cliente_id', 'Cliente')->required('placa', 'Placa')
            ->required('marca', 'Marca')->required('modelo', 'Modelo');
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $id = Veiculo::criar($dados, Auth::id());
        $this->jsonOk(['id' => $id], 201);
    }

    public function update(Request $request, array $params): void
    {
        $id = (int) $params['id'];
        $dados = [
            'placa' => $request->string('placa'),
            'chassi' => $request->string('chassi') ?: null,
            'marca' => $request->string('marca'),
            'modelo' => $request->string('modelo'),
            'ano' => $request->int('ano') ?: null,
            'cor' => $request->string('cor') ?: null,
            'km_atual' => $request->int('km_atual'),
        ];
        Veiculo::atualizar($id, $dados);
        $this->jsonOk(['id' => $id]);
    }

    public function destroy(Request $request, array $params): void
    {
        Veiculo::softDelete((int) $params['id']);
        $this->jsonOk();
    }

    public function listarPorCliente(Request $request, array $params): void
    {
        $clienteId = (int) $params['clienteId'];
        $this->jsonOk(Veiculo::listarPorCliente($clienteId, $request->query));
    }

    public function buscarPlaca(Request $request, array $params): void
    {
        $placa = $request->string('placa');
        if ($placa === '') {
            $this->jsonOk(null);
        }
        $veiculo = Veiculo::findByPlaca($placa);
        $this->jsonOk($veiculo);
    }

    public function historico(Request $request, array $params): void
    {
        $this->jsonOk(RelatorioService::historicoVeiculo((int) $params['id']));
    }
}
