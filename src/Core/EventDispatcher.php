<?php

declare(strict_types=1);

namespace App\Core;

/** Dispatcher simples para desacoplar efeitos colaterais (logs, reservas, auditoria). */
final class EventDispatcher
{
    /** @var array<string, list<callable>> */
    private static array $listeners = [];

    public static function listen(string $event, callable $listener): void
    {
        self::$listeners[$event][] = $listener;
    }

    /** @param array<string, mixed> $payload */
    public static function dispatch(string $event, array $payload = []): void
    {
        foreach (self::$listeners[$event] ?? [] as $listener) {
            $listener($payload);
        }
    }
}
