<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: callable, auth: bool, adminOnly: bool}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler, bool $auth = true, bool $adminOnly = false): void
    {
        $this->add('GET', $pattern, $handler, $auth, $adminOnly);
    }

    public function post(string $pattern, callable $handler, bool $auth = true, bool $adminOnly = false): void
    {
        $this->add('POST', $pattern, $handler, $auth, $adminOnly);
    }

    public function put(string $pattern, callable $handler, bool $auth = true, bool $adminOnly = false): void
    {
        $this->add('PUT', $pattern, $handler, $auth, $adminOnly);
    }

    public function delete(string $pattern, callable $handler, bool $auth = true, bool $adminOnly = false): void
    {
        $this->add('DELETE', $pattern, $handler, $auth, $adminOnly);
    }

    private function add(string $method, string $pattern, callable $handler, bool $auth, bool $adminOnly): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'auth' => $auth,
            'adminOnly' => $adminOnly,
        ];
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = $this->match($route['pattern'], $request->path);
            if ($params === null) {
                continue;
            }

            if ($route['auth'] && !Auth::check()) {
                if ($request->wantsJson()) {
                    Response::json(['erro' => 'Não autenticado'], 401);
                }
                Response::redirect('/login');
            }

            if ($route['adminOnly'] && !Auth::isAdmin()) {
                if ($request->wantsJson()) {
                    Response::json(['erro' => 'Acesso negado'], 403);
                }
                Response::redirect('/');
            }

            if ($route['auth'] && !Auth::canAccessPath($request->path)) {
                if ($request->wantsJson()) {
                    Response::json(['erro' => 'Sem permissão para este recurso'], 403);
                }
                Response::redirect('/');
            }

            $trocarSenhaLivre = ['/trocar-senha', '/logout'];
            if ($route['auth'] && Auth::id() !== null && User::deveTrocarSenha(Auth::id())
                && !in_array($request->path, $trocarSenhaLivre, true)
                && !str_starts_with($request->path, '/trocar-senha')) {
                if ($request->wantsJson()) {
                    Response::json(['erro' => 'Troque sua senha para continuar', 'redirect' => '/trocar-senha'], 403);
                }
                Response::redirect('/trocar-senha');
            }

            ($route['handler'])($request, $params);
            return;
        }

        if ($request->wantsJson()) {
            Response::json(['erro' => 'Rota não encontrada'], 404);
        }

        http_response_code(404);
        View::render('errors/404', ['titulo' => 'Página não encontrada']);
        exit;
    }

    /** @return array<string, string>|null */
    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
