<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Database;

App::bootstrap();
$pdo = Database::pdo();

$dir = $root . '/migrations';
$files = glob($dir . '/*.sql');
sort($files);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        arquivo VARCHAR(120) NOT NULL UNIQUE,
        executado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB'
);

foreach ($files as $file) {
    $nome = basename($file);
    $chk = $pdo->prepare('SELECT 1 FROM migrations_log WHERE arquivo = :a LIMIT 1');
    $chk->execute(['a' => $nome]);
    if ($chk->fetchColumn()) {
        echo "Pulando {$nome}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Não leu {$file}");
    }

    echo "Executando {$nome}...\n";
    $statements = preg_split('/;\s*(?:\r?\n|$)/', trim($sql)) ?: [];
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
    $ins = $pdo->prepare('INSERT INTO migrations_log (arquivo) VALUES (:a)');
    $ins->execute(['a' => $nome]);
}

echo "Migrations concluídas.\n";
