#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Services\NotificationService;

\App\Core\App::bootstrap();
$n = NotificationService::processar(50);
echo "Processadas: {$n}\n";
