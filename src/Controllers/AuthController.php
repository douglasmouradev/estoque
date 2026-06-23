<?php



declare(strict_types=1);



namespace App\Controllers;



use App\Core\Auth;

use App\Core\Controller;

use App\Core\Logger;

use App\Core\RateLimiter;

use App\Core\Request;

use App\Core\Response;

use App\Core\Session;

use App\Core\Validator;
use App\Models\User;



final class AuthController extends Controller

{

    public function loginForm(Request $request, array $params): void

    {

        if (Auth::check()) {

            Response::redirect('/');

        }

        $this->view('auth/login', ['titulo' => 'Entrar'], null);

    }



    public function login(Request $request, array $params): void

    {

        $email = $request->string('email');

        $senha = $request->string('password') ?: $request->string('senha');

        $rateKey = 'login_' . md5($email . '|' . ($request->server['REMOTE_ADDR'] ?? 'local'));



        if (RateLimiter::tooMany($rateKey, 5, 900)) {

            Logger::info('Login bloqueado por rate limit', ['email' => $email]);

            if ($request->wantsJson()) {

                $this->jsonErro('Muitas tentativas. Aguarde 15 minutos.', 429);

            }

            Session::flash('erro', 'Muitas tentativas. Aguarde 15 minutos.');

            $this->view('auth/login', ['titulo' => 'Entrar'], null);

        }



        $v = new Validator(['email' => $email, 'senha' => $senha]);

        $v->required('email', 'E-mail')->email('email', 'E-mail')->required('senha', 'Senha');

        if ($v->falhou()) {

            if ($request->wantsJson()) {

                $this->jsonValidacao($v->erros());

            }

            Session::flash('erro', 'Preencha e-mail e senha.');

            $this->view('auth/login', ['titulo' => 'Entrar'], null);

        }



        if (!Auth::attempt($email, $senha)) {

            Logger::info('Login falhou', ['email' => $email]);

            if ($request->wantsJson()) {

                $this->jsonErro('Credenciais inválidas', 401);

            }

            Session::flash('erro', 'E-mail ou senha incorretos.');

            $this->view('auth/login', ['titulo' => 'Entrar'], null);

        }



        RateLimiter::clear($rateKey);



        if ($request->wantsJson()) {

            $this->jsonOk(['redirect' => User::deveTrocarSenha((int) Auth::id()) ? '/trocar-senha' : '/']);

        }

        Response::redirect(User::deveTrocarSenha((int) Auth::id()) ? '/trocar-senha' : '/');

    }



    public function logout(Request $request, array $params): void

    {

        Auth::logout();

        if ($request->wantsJson()) {

            $this->jsonOk(['redirect' => '/login']);

        }

        Response::redirect('/login');

    }

}

