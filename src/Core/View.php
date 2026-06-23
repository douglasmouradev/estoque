<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private static string $basePath;

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/\\');
    }

    /** URL base para assets (funciona com php -S e Apache). */
    public static function asset(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base = $scriptDir === '/' || $scriptDir === '\\' ? '' : rtrim(str_replace('\\', '/', $scriptDir), '/');
        return $base . $path;
    }

    public static function iniciaisUsuario(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome)) ?: [];
        $out = '';
        foreach ($partes as $p) {
            if ($p === '') {
                continue;
            }
            $out .= strtoupper(substr($p, 0, 1));
            if (strlen($out) >= 2) {
                break;
            }
        }
        return $out !== '' ? $out : 'U';
    }

    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $viewFile = self::$basePath . '/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $usuario = Auth::user();
        $usuarioNome = Session::get('user_nome', '');
        $usuarioPerfil = Auth::perfil()?->label() ?? '';
        $asset = static fn (string $p): string => self::asset($p);
        $iniciais = self::iniciaisUsuario((string) $usuarioNome);
        $csrfToken = Csrf::token();
        $perfilAtual = Auth::perfil()?->value ?? '';
        $podeEscrever = Auth::perfil() !== \App\Enums\PerfilUsuario::Mecanico;
        $isAdmin = Auth::isAdmin();

        ob_start();
        require $viewFile;
        $content = ob_get_clean() ?: '';

        if ($layout === null) {
            Response::html($content);
        }

        $layoutFile = self::$basePath . '/' . str_replace('.', '/', $layout) . '.php';
        if (!is_file($layoutFile)) {
            Response::html($content);
        }

        ob_start();
        require $layoutFile;
        Response::html(ob_get_clean() ?: '');
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $partial, array $data = []): string
    {
        $file = self::$basePath . '/' . str_replace('.', '/', $partial) . '.php';
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean() ?: '';
    }
}
