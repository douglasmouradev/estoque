<?php



declare(strict_types=1);



namespace App\Controllers;



use App\Core\Auth;

use App\Core\Controller;

use App\Core\Request;

use App\Core\Validator;

use App\Enums\PerfilUsuario;

use App\Models\User;



final class UserController extends Controller

{

    public function index(Request $request, array $params): void

    {

        if ($request->wantsJson()) {

            $this->jsonOk(User::listar($request->query));

        }

        $this->view('usuarios/index', ['titulo' => 'Usuários']);

    }



    public function store(Request $request, array $params): void

    {

        $dados = [

            'nome' => $request->string('nome'),

            'email' => $request->string('email'),

            'senha' => $request->string('senha'),

            'perfil' => $request->string('perfil', 'mecanico'),

        ];

        $v = new Validator($dados);

        $v->required('nome', 'Nome')->required('email', 'E-mail')->email('email', 'E-mail')

            ->required('senha', 'Senha')->minLength('senha', 6, 'Senha')

            ->inEnum('perfil', array_column(PerfilUsuario::cases(), 'value'), 'Perfil');

        if ($v->falhou()) {

            $this->jsonValidacao($v->erros());

        }

        try {

            $id = User::criar($dados, Auth::id());

        } catch (\Throwable $e) {

            $this->jsonErro($e->getMessage(), 400);

        }

        $this->jsonOk(['id' => $id], 201);

    }



    public function update(Request $request, array $params): void

    {

        $dados = [

            'nome' => $request->string('nome'),

            'email' => $request->string('email'),

            'senha' => $request->string('senha'),

            'perfil' => $request->string('perfil'),

            'ativo' => $request->bool('ativo', true),

        ];

        $v = new Validator($dados);

        $v->required('nome', 'Nome')->required('email', 'E-mail')->email('email', 'E-mail')

            ->inEnum('perfil', array_column(PerfilUsuario::cases(), 'value'), 'Perfil');

        if ($dados['senha'] !== '') {

            $v->minLength('senha', 6, 'Senha');

        }

        if ($v->falhou()) {

            $this->jsonValidacao($v->erros());

        }

        try {

            User::atualizar((int) $params['id'], $dados);

        } catch (\Throwable $e) {

            $this->jsonErro($e->getMessage(), 400);

        }

        $this->jsonOk();

    }



    public function destroy(Request $request, array $params): void

    {

        $id = (int) $params['id'];

        if ($id === Auth::id()) {

            $this->jsonErro('Não é possível excluir seu próprio usuário.', 400);

        }

        User::softDelete($id);

        $this->jsonOk();

    }

}

