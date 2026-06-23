<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

final class MailService
{
    public static function enviar(string $para, string $assunto, string $html): bool
    {
        $from = $_ENV['MAIL_FROM'] ?? 'noreply@oficina.local';
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Oficina';
        $driver = $_ENV['MAIL_DRIVER'] ?? 'log';

        if ($driver === 'smtp' && !empty($_ENV['MAIL_HOST'])) {
            return self::enviarSmtp($para, $assunto, $html, $from, $fromName);
        }

        if ($driver === 'log') {
            Logger::info('E-mail (log)', ['para' => $para, 'assunto' => $assunto, 'html' => mb_substr($html, 0, 500)]);
            return true;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . self::encodeAddress($fromName, $from),
        ];
        $ok = @mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $html, implode("\r\n", $headers));
        if (!$ok) {
            Logger::info('Falha mail()', ['para' => $para, 'assunto' => $assunto]);
        }
        return $ok;
    }

    private static function encodeAddress(string $name, string $email): string
    {
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
    }

    private static function enviarSmtp(string $para, string $assunto, string $html, string $from, string $fromName): bool
    {
        $host = $_ENV['MAIL_HOST'] ?? '';
        $port = (int) ($_ENV['MAIL_PORT'] ?? 587);
        $user = $_ENV['MAIL_USERNAME'] ?? '';
        $pass = $_ENV['MAIL_PASSWORD'] ?? '';

        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, 10);
            if (!$socket) {
                Logger::info('SMTP connect failed', ['host' => $host, 'err' => $errstr]);
                return false;
            }
            stream_set_timeout($socket, 10);
            self::smtpRead($socket);
            fwrite($socket, "EHLO localhost\r\n");
            self::smtpRead($socket);
            if ($user !== '') {
                fwrite($socket, "AUTH LOGIN\r\n");
                self::smtpRead($socket);
                fwrite($socket, base64_encode($user) . "\r\n");
                self::smtpRead($socket);
                fwrite($socket, base64_encode($pass) . "\r\n");
                self::smtpRead($socket);
            }
            fwrite($socket, 'MAIL FROM:<' . $from . ">\r\n");
            self::smtpRead($socket);
            fwrite($socket, 'RCPT TO:<' . $para . ">\r\n");
            self::smtpRead($socket);
            fwrite($socket, "DATA\r\n");
            self::smtpRead($socket);
            $body = "From: " . self::encodeAddress($fromName, $from) . "\r\n"
                . "To: <{$para}>\r\n"
                . 'Subject: =?UTF-8?B?' . base64_encode($assunto) . "?=\r\n"
                . "MIME-Version: 1.0\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $html . "\r\n.\r\n";
            fwrite($socket, $body);
            self::smtpRead($socket);
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        } catch (\Throwable $e) {
            Logger::info('SMTP error', ['msg' => $e->getMessage()]);
            return false;
        }
    }

    /** @param resource $socket */
    private static function smtpRead($socket): void
    {
        $line = '';
        while ($chunk = fgets($socket, 512)) {
            $line .= $chunk;
            if (isset($chunk[3]) && $chunk[3] === ' ') {
                break;
            }
        }
    }

    public static function templateOrcamento(string $nomeCliente, int $numero, string $link, string $oficinaNome): string
    {
        $nome = htmlspecialchars($nomeCliente, ENT_QUOTES, 'UTF-8');
        $oficina = htmlspecialchars($oficinaNome, ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html><html><body style=\"font-family:sans-serif\">"
            . "<h2>{$oficina}</h2>"
            . "<p>Olá, {$nome}!</p>"
            . "<p>Seu orçamento <strong>#{$numero}</strong> está disponível para análise.</p>"
            . "<p><a href=\"{$url}\">Clique aqui para visualizar e responder</a></p>"
            . "<p style=\"color:#666;font-size:12px\">Link válido por 30 dias.</p></body></html>";
    }

    public static function templateResetSenha(string $link): string
    {
        $url = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html><html><body style=\"font-family:sans-serif\">"
            . "<h2>Redefinição de senha</h2>"
            . "<p>Recebemos uma solicitação para redefinir sua senha.</p>"
            . "<p><a href=\"{$url}\">Redefinir senha</a></p>"
            . "<p style=\"color:#666;font-size:12px\">Se não foi você, ignore este e-mail. O link expira em 1 hora.</p></body></html>";
    }
}
