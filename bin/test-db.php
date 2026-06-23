<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();

try {
    $pdo = Database::pdo();
    $pdo->query('SELECT 1');
    echo "OK — conectado ao banco.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
