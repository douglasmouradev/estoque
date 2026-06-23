<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;

final class HealthController extends Controller
{
    public function check(Request $request, array $params): void
    {
        $ok = true;
        $checks = ['app' => 'ok'];

        try {
            Database::pdo()->query('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $ok = false;
            $checks['database'] = 'fail';
        }

        $this->jsonOk([
            'status' => $ok ? 'healthy' : 'degraded',
            'checks' => $checks,
            'time' => date('c'),
        ], $ok ? 200 : 503);
    }
}
