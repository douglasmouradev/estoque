<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Configuracao;
use App\Models\OrdemServico;

final class RelatorioService
{
    /** @return array<string, mixed> */
    public static function dashboard(): array
    {
        $pdo = Database::pdo();
        $osAbertas = (int) $pdo->query(
            "SELECT COUNT(*) FROM ordens_servico WHERE status NOT IN ('finalizada','cancelada')"
        )->fetchColumn();
        $orcPendentes = (int) $pdo->query(
            "SELECT COUNT(*) FROM orcamentos WHERE status = 'enviado'"
        )->fetchColumn();
        $financeiroPendente = (float) $pdo->query(
            "SELECT COALESCE(SUM(valor_total - valor_pago), 0) FROM ordens_servico
             WHERE status = 'finalizada' AND status_pagamento != 'pago'"
        )->fetchColumn();

        return [
            'os_abertas' => $osAbertas,
            'orcamentos_aguardando' => $orcPendentes,
            'financeiro_pendente' => round($financeiroPendente, 2),
            'pecas_abaixo_minimo' => \App\Models\Peca::listar(['abaixo_minimo' => '1', 'per_page' => 50, 'page' => 1])['itens'],
            'pecas_paradas' => \App\Models\Peca::paradas(Configuracao::diasPecasParadas()),
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function historicoVeiculo(int $veiculoId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT 'orcamento' AS tipo, o.id, o.numero, o.status, o.created_at
             FROM orcamentos o WHERE o.veiculo_id = :vid
             UNION ALL
             SELECT 'os' AS tipo, os.id, os.numero, os.status, os.created_at
             FROM ordens_servico os WHERE os.veiculo_id = :vid2
             ORDER BY created_at DESC LIMIT 50"
        );
        $stmt->execute(['vid' => $veiculoId, 'vid2' => $veiculoId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed> */
    public static function buscaGlobal(string $termo): array
    {
        if (mb_strlen(trim($termo)) < 2) {
            return ['clientes' => [], 'veiculos' => [], 'pecas' => [], 'ordens_servico' => []];
        }
        $q = '%' . $termo . '%';
        $pdo = Database::pdo();

        $clientes = $pdo->prepare(
            'SELECT id, nome, cpf_cnpj FROM clientes WHERE deleted_at IS NULL AND (nome LIKE :q OR cpf_cnpj LIKE :q2) LIMIT 8'
        );
        $clientes->execute(['q' => $q, 'q2' => $q]);
        $veiculos = $pdo->prepare(
            'SELECT v.id, v.cliente_id, v.placa, v.marca, v.modelo, c.nome AS cliente_nome
             FROM veiculos v INNER JOIN clientes c ON c.id = v.cliente_id
             WHERE v.deleted_at IS NULL AND v.placa LIKE :q LIMIT 8'
        );
        $veiculos->execute(['q' => $q]);
        $pecas = $pdo->prepare(
            'SELECT id, codigo_interno, descricao FROM pecas
             WHERE deleted_at IS NULL AND (codigo_interno LIKE :q OR descricao LIKE :q2) LIMIT 8'
        );
        $pecas->execute(['q' => $q, 'q2' => $q]);
        $os = $pdo->prepare(
            'SELECT os.id, os.numero, os.status, c.nome AS cliente_nome
             FROM ordens_servico os INNER JOIN clientes c ON c.id = os.cliente_id
             WHERE CAST(os.numero AS CHAR) LIKE :q OR c.nome LIKE :q2 LIMIT 8'
        );
        $os->execute(['q' => $q, 'q2' => $q]);

        return [
            'clientes' => $clientes->fetchAll(),
            'veiculos' => $veiculos->fetchAll(),
            'pecas' => $pecas->fetchAll(),
            'ordens_servico' => $os->fetchAll(),
        ];
    }
}
