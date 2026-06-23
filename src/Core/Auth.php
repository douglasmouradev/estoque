<?php

declare(strict_types=1);

namespace App\Core;

use App\Enums\PerfilUsuario;
use App\Models\User;

final class Auth
{
    private const SESSION_USER_KEY = 'user_id';

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        Session::set(self::SESSION_USER_KEY, (int) $user['id']);
        Session::set('user_perfil', $user['perfil']);
        Session::set('user_nome', $user['nome']);

        return true;
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_USER_KEY);
        Session::forget('user_perfil');
        Session::forget('user_nome');
        session_regenerate_id(true);
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_USER_KEY);
        return $id !== null ? (int) $id : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        return $id !== null ? User::findById($id) : null;
    }

    public static function perfil(): ?PerfilUsuario
    {
        $p = Session::get('user_perfil');
        return $p !== null ? PerfilUsuario::from($p) : null;
    }

    public static function canAccessPath(string $path): bool
    {
        $perfil = self::perfil();
        if ($perfil === null) {
            return false;
        }

        foreach ($perfil->rotasBloqueadas() as $bloqueada) {
            if (str_starts_with($path, $bloqueada)) {
                return false;
            }
        }

        return true;
    }

    public static function isAdmin(): bool
    {
        return self::perfil() === PerfilUsuario::Admin;
    }
}
