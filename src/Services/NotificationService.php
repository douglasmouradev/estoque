<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;

final class NotificationService
{
    public static function enfileirarEmail(string $para, string $assunto, string $html): void
    {
        if ($para === '' || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        Database::pdo()->prepare(
            'INSERT INTO notification_queue (tipo, destinatario, assunto, corpo) VALUES (:t, :d, :a, :c)'
        )->execute(['t' => 'email', 'd' => $para, 'a' => $assunto, 'c' => $html]);
    }

    public static function processar(int $limite = 20): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT id, destinatario, assunto, corpo, tentativas FROM notification_queue
             WHERE status = 'pendente' AND agendado_em <= NOW() ORDER BY id ASC LIMIT :lim"
        );
        $stmt->bindValue('lim', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        $enviados = 0;
        foreach ($stmt->fetchAll() as $row) {
            $ok = MailService::enviar($row['destinatario'], $row['assunto'], $row['corpo']);
            if ($ok) {
                $pdo->prepare(
                    "UPDATE notification_queue SET status = 'enviado', enviado_em = NOW() WHERE id = :id"
                )->execute(['id' => $row['id']]);
                $enviados++;
            } else {
                $tent = (int) $row['tentativas'] + 1;
                $status = $tent >= 3 ? 'erro' : 'pendente';
                $pdo->prepare(
                    'UPDATE notification_queue SET tentativas = :t, status = :st, erro_msg = :err WHERE id = :id'
                )->execute(['t' => $tent, 'st' => $status, 'err' => 'Falha no envio', 'id' => $row['id']]);
            }
        }
        if ($enviados > 0) {
            Logger::info('Notificações processadas', ['enviados' => $enviados]);
        }
        return $enviados;
    }
}
