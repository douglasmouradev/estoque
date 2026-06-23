#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$outDir = $root . '/storage/backups';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db = $_ENV['DB_DATABASE'] ?? 'oficina_estoque';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$file = $outDir . '/backup_' . date('Y-m-d_His') . '.sql';

$cmd = sprintf(
    'mysqldump -h %s -u %s %s %s > %s',
    escapeshellarg($host),
    escapeshellarg($user),
    $pass !== '' ? '-p' . escapeshellarg($pass) : '',
    escapeshellarg($db),
    escapeshellarg($file)
);

exec($cmd, $output, $code);
if ($code !== 0) {
    fwrite(STDERR, "mysqldump falhou (código {$code}). Instale o cliente MySQL.\n");
    exit(1);
}

echo "Backup salvo: {$file}\n";
