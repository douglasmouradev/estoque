<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\IpRateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Services\MailService;
use App\Services\NotificationService;

final class PasswordResetController extends Controller
{
    public function formEsqueci(Request $request, array $params): void
    {
        $this->view('auth/esqueci-senha', ['titulo' => 'Esqueci minha senha'], null);
    }

    public function solicitar(Request $request, array $params): void
    {
        $email = $request->string('email');
        if (IpRateLimiter::tooMany('reset_' . md5($email), 3, 3600)) {
            $this->jsonErro('Muitas solicitações. Tente novamente em 1 hora.', 429);
        }
        $v = new Validator(['email' => $email]);
        $v->required('email', 'E-mail')->email('email', 'E-mail');
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        $token = User::criarTokenReset($email);
        if ($token !== null) {
            $base = rtrim((require dirname(__DIR__, 2) . '/config/app.php')['url'], '/');
            $link = $base . '/redefinir-senha?token=' . urlencode($token);
            $html = MailService::templateResetSenha($link);
            NotificationService::enfileirarEmail($email, 'Redefinição de senha — Oficina', $html);
            NotificationService::processar(5);
        }
        if ($request->wantsJson()) {
            $this->jsonOk(['mensagem' => 'Se o e-mail existir, enviaremos instruções.']);
        }
        Session::flash('sucesso', 'Se o e-mail existir, enviaremos instruções.');
        Response::redirect('/login');
    }

    public function formRedefinir(Request $request, array $params): void
    {
        $token = $request->string('token');
        if ($token === '' || User::validarTokenReset($token) === null) {
            $this->view('auth/redefinir-senha-erro', ['titulo' => 'Link inválido'], null);
            return;
        }
        $this->view('auth/redefinir-senha', ['titulo' => 'Nova senha', 'token' => $token], null);
    }

    public function redefinir(Request $request, array $params): void
    {
        $token = $request->string('token');
        $senha = $request->string('senha');
        $conf = $request->string('senha_confirmacao');
        $v = new Validator(['senha' => $senha]);
        $v->required('senha', 'Senha')->minLength('senha', 6, 'Senha');
        if ($senha !== $conf) {
            $this->jsonErro('As senhas não conferem.');
        }
        if ($v->falhou()) {
            $this->jsonValidacao($v->erros());
        }
        if (!User::redefinirSenhaPorToken($token, $senha)) {
            $this->jsonErro('Link inválido ou expirado.', 400);
        }
        if ($request->wantsJson()) {
            $this->jsonOk(['redirect' => '/login']);
        }
        Response::redirect('/login');
    }
}
