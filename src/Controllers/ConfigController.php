<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Models\Configuracao;

final class ConfigController extends Controller
{
    public function index(Request $request, array $params): void
    {
        if ($request->wantsJson()) {
            $this->jsonOk([
                'pecas_paradas_dias' => Configuracao::diasPecasParadas(),
                'oficina' => Configuracao::oficina(),
            ]);
        }
        $this->view('config/index', ['titulo' => 'Configurações']);
    }

    public function salvar(Request $request, array $params): void
    {
        $dias = $request->int('pecas_paradas_dias');
        if ($dias >= 1 && $dias <= 3650) {
            Configuracao::set('pecas_paradas_dias', (string) $dias, Auth::id());
        }
        Configuracao::salvarOficina([
            'nome' => $request->string('oficina_nome'),
            'cnpj' => $request->string('oficina_cnpj'),
            'telefone' => $request->string('oficina_telefone'),
            'email' => $request->string('oficina_email'),
            'endereco' => $request->string('oficina_endereco'),
        ], Auth::id());
        $this->jsonOk();
    }
}
