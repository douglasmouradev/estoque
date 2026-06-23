<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\CategoriaPeca;

final class CategoriaController extends Controller
{
    public function index(Request $request, array $params): void
    {
        $this->jsonOk(CategoriaPeca::listar());
    }

    public function store(Request $request, array $params): void
    {
        $this->exigeEscrita();
        $nome = $request->string('nome');
        if ($nome === '') {
            $this->jsonErro('Nome é obrigatório.');
        }
        $id = CategoriaPeca::criar($nome);
        $this->jsonOk(['id' => $id], 201);
    }

    public function update(Request $request, array $params): void
    {
        $this->exigeEscrita();
        CategoriaPeca::atualizar((int) $params['id'], $request->string('nome'));
        $this->jsonOk();
    }

    public function destroy(Request $request, array $params): void
    {
        $this->exigeEscrita();
        CategoriaPeca::remover((int) $params['id']);
        $this->jsonOk();
    }
}
