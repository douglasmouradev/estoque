<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function jsonOk(mixed $data = null, int $status = 200): never
    {
        Response::json(['sucesso' => true, 'dados' => $data], $status);
    }

    protected function jsonErro(string $mensagem, int $status = 400, array $extras = []): never
    {
        Response::json(array_merge(['sucesso' => false, 'erro' => $mensagem], $extras), $status);
    }

    protected function jsonValidacao(array $erros): never
    {
        $this->jsonErro('Dados inválidos', 422, ['erros' => $erros]);
    }

    /** @param array<string, mixed> $data */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): never
    {
        View::render($view, $data, $layout);
    }

    protected function exigeEscrita(): void
    {
        if (\App\Core\Auth::perfil() === \App\Enums\PerfilUsuario::Mecanico) {
            $this->jsonErro('Perfil mecânico possui apenas leitura.', 403);
        }
    }

    protected function naoEncontrado(Request $request, string $msg = 'Não encontrado'): never
    {
        if ($request->wantsJson()) {
            $this->jsonErro($msg, 404);
        }
        $this->view('errors/404', ['titulo' => 'Não encontrado']);
    }
}
