<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\EventDispatcher;
use App\Enums\StatusOrcamento;
use App\Models\AuditLog;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\ReservaEstoque;

/** Regras de negócio de orçamentos — ponto único para evolução. */
final class OrcamentoService
{
    public static function bootListeners(): void
    {
        EventDispatcher::listen('orcamento.aprovado', static function (array $p): void {
            AuditLog::registrar($p['user_id'] ?? null, 'aprovar', 'orcamento', $p['id'] ?? null);
        });
        EventDispatcher::listen('orcamento.enviado', static function (array $p): void {
            AuditLog::registrar($p['user_id'] ?? null, 'enviar', 'orcamento', $p['id'] ?? null);
        });
    }

    public static function enviar(int $id, ?int $userId): void
    {
        $orc = Orcamento::findById($id);
        if ($orc === null) {
            throw new \RuntimeException('Orçamento não encontrado.');
        }
        if (!in_array($orc['status'], [StatusOrcamento::Rascunho->value, StatusOrcamento::Reprovado->value], true)) {
            throw new \RuntimeException('Status atual não permite envio.');
        }
        $token = bin2hex(random_bytes(24));
        Orcamento::definirToken($id, $token, 30);
        Orcamento::alterarStatus($id, StatusOrcamento::Enviado);
        EventDispatcher::dispatch('orcamento.enviado', ['id' => $id, 'user_id' => $userId, 'token' => $token]);
    }

    public static function aprovar(int $id, ?int $userId, ?string $obs = null, bool $viaPortal = false): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Orcamento::alterarStatus($id, StatusOrcamento::Aprovado, $obs);
            ReservaEstoque::criarReservasOrcamento($id);
            $pdo->commit();
            EventDispatcher::dispatch('orcamento.aprovado', [
                'id' => $id,
                'user_id' => $userId,
                'via_portal' => $viaPortal,
            ]);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function reprovar(int $id, ?string $obs, ?int $userId): void
    {
        ReservaEstoque::liberarPorOrcamento($id);
        Orcamento::alterarStatus($id, StatusOrcamento::Reprovado, $obs);
        AuditLog::registrar($userId, 'reprovar', 'orcamento', $id);
    }

    public static function converterOs(int $orcamentoId, ?int $userId): int
    {
        $osId = OrdemServico::criarDeOrcamento($orcamentoId, $userId);
        ReservaEstoque::consumirPorOrcamento($orcamentoId);
        AuditLog::registrar($userId, 'converter_os', 'orcamento', $orcamentoId, ['os_id' => $osId]);
        return $osId;
    }

    public static function linkPortal(int $id): ?string
    {
        $orc = Orcamento::findById($id);
        if ($orc === null || empty($orc['token_acesso'])) {
            return null;
        }
        $base = rtrim((string) (require dirname(__DIR__, 2) . '/config/app.php')['url'], '/');
        return $base . '/portal/orcamento/' . $orc['token_acesso'];
    }

    /** @return array<string, mixed>|null */
    public static function porToken(string $token): ?array
    {
        $orc = Orcamento::findByToken($token);
        if ($orc === null) {
            return null;
        }
        $orc['itens'] = Orcamento::itens((int) $orc['id']);
        $orc['totais'] = Orcamento::calcularTotais(
            $orc['itens'],
            (float) $orc['desconto_geral_percent'],
            (float) $orc['desconto_geral_valor']
        );
        return $orc;
    }
}
