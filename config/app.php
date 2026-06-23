<?php

declare(strict_types=1);

return [
    'name' => 'Oficina — Estoque & Orçamentos',
    'env' => $_ENV['APP_ENV'] ?? 'local',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
    'timezone' => 'America/Sao_Paulo',
    'session_name' => 'oficina_session',
    'session_lifetime' => 7200,
    'upload_max_mb' => 5,
    'pecas_paradas_dias_default' => 90,
];
