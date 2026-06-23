<?php

declare(strict_types=1);

// Document root — único ponto de entrada HTTP
$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Request;
use App\Core\SecurityHeaders;

App::bootstrap();
SecurityHeaders::apply();

$request = Request::capture();

$csrfLivre = in_array($request->path, ['/login'], true)
    || str_starts_with($request->path, '/portal/');
if (!$csrfLivre && in_array($request->method, ['POST', 'PUT', 'DELETE'], true)) {
    if (!\App\Core\Csrf::validateRequest($request)) {
        if ($request->wantsJson()) {
            \App\Core\Response::json(['sucesso' => false, 'erro' => 'Token CSRF inválido. Recarregue a página.'], 419);
        }
        http_response_code(419);
        echo 'Sessão expirada. Recarregue a página.';
        exit;
    }
}

// PUT/DELETE via _method ou header (forms HTML)
if ($request->method === 'POST') {
    $override = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
    if (is_string($override)) {
        $_SERVER['REQUEST_METHOD'] = strtoupper($override);
        $request = Request::capture();
    }
}

/** @var \App\Core\Router $router */
$router = require $root . '/src/routes.php';
$router->dispatch($request);
