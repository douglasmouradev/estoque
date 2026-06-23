<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class OpenApiController extends Controller
{
    public function spec(Request $request, array $params): void
    {
        $file = dirname(__DIR__, 2) . '/docs/openapi.yaml';
        if (!is_file($file)) {
            $this->jsonErro('Especificação não encontrada', 404);
        }
        header('Content-Type: application/yaml; charset=utf-8');
        readfile($file);
        exit;
    }

    public function docs(Request $request, array $params): void
    {
        $yaml = htmlspecialchars((require dirname(__DIR__, 2) . '/config/app.php')['url'] . '/api/openapi.yaml');
        Response::html('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>API Docs</title>'
            . '<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css"></head><body>'
            . '<div id="swagger"></div>'
            . '<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>'
            . '<script>SwaggerUIBundle({url:"' . $yaml . '",dom_id:"#swagger"});</script></body></html>');
    }
}
