<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

final class SenhaController extends Controller
{
    public function form(Request $request, array $params): void
    {
        if (!Auth::check()) {
            Response::redirect('/login');
        }
        $this->view('auth/trocar-senha', ['titulo' => 'Trocar senha'], null);
    }

    public function atualizar(Request $request, array $params): void
    {
        $senha = $request->string('senha');
        $conf = $request->string('senha_confirmacao');
        $v = new Validator(['senha' => $senha, 'senha_confirmacao' => $conf]);
        $v->required('senha', 'Senha')->minLength('senha', 6, 'Senha');
        if ($senha !== $conf) {
            $this->jsonErro('As senhas não conferem.');
        }
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $id = Auth::id();
        User::atualizar($id, ['nome' => Session::get('user_nome', ''), 'email' => Auth::user()['email'] ?? '', 'perfil' => Session::get('user_perfil', ''), 'ativo' => true, 'senha' => $senha]);
        User::marcarSenhaAlterada($id);
        if ($request->wantsJson()) {
            $this->jsonOk(['redirect' => '/']);
        }
        Response::redirect('/');
    }
}
