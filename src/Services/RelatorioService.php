<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Configuracao;
use App\Models\Peca;

final class RelatorioService
{
    /** @return array<string, mixed> */
    public static function dashboard(?string $de = null, ?string $ate = null): array
    {
        $pdo = Database::pdo();
        $filtroData = self::filtroDataSql('os.created_at', $de, $ate, 'df');
        $params = $filtroData['params'];

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

        $faturamento = self::faturamentoPeriodo($de, $ate);
        $mecanicos = self::produtividadeMecanicos($de, $ate);

        return [
            'os_abertas' => $osAbertas,
            'orcamentos_aguardando' => $orcPendentes,
            'financeiro_pendente' => round($financeiroPendente, 2),
            'faturamento_periodo' => $faturamento,
            'produtividade_mecanicos' => $mecanicos,
            'pecas_abaixo_minimo' => Peca::listar(['abaixo_minimo' => '1', 'per_page' => 50, 'page' => 1])['itens'],
            'pecas_paradas' => Peca::paradas(Configuracao::diasPecasParadas()),
        ];
    }

    /** @return array{total: float, os_finalizadas: int} */
    public static function faturamentoPeriodo(?string $de, ?string $ate): array
    {
        $pdo = Database::pdo();
        $f = self::filtroDataSql('finalizada_em', $de, $ate, 'fp');
        $sql = "SELECT COALESCE(SUM(valor_total), 0), COUNT(*) FROM ordens_servico
                WHERE status = 'finalizada' AND finalizada_em IS NOT NULL {$f['sql']}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($f['params']);
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return ['total' => round((float) ($row[0] ?? 0), 2), 'os_finalizadas' => (int) ($row[1] ?? 0)];
    }

    /** @return list<array<string, mixed>> */
    public static function produtividadeMecanicos(?string $de, ?string $ate): array
    {
        $pdo = Database::pdo();
        $f = self::filtroDataSql('h.data_trabalho', $de, $ate, 'pm');
        $stmt = $pdo->prepare(
            "SELECT u.nome, SUM(h.horas) AS total_horas, COUNT(DISTINCT h.ordem_servico_id) AS os_count
             FROM os_horas h INNER JOIN users u ON u.id = h.mecanico_id
             WHERE 1=1 {$f['sql']} GROUP BY u.id, u.nome ORDER BY total_horas DESC LIMIT 20"
        );
        $stmt->execute($f['params']);
        return $stmt->fetchAll();
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

    public static function exportarCsv(string $tipo, ?string $de, ?string $ate): string
    {
        if ($tipo === 'financeiro') {
            return FinanceiroService::exportarCsv(['page' => 1, 'per_page' => 1000]);
        }
        if ($tipo === 'mecanicos') {
            $rows = self::produtividadeMecanicos($de, $ate);
            $out = "Mecânico;Horas;OS\n";
            foreach ($rows as $r) {
                $out .= "{$r['nome']};{$r['total_horas']};{$r['os_count']}\n";
            }
            return $out;
        }
        $d = self::dashboard($de, $ate);
        $out = "Código;Descrição;Saldo;Mínimo\n";
        foreach ($d['pecas_abaixo_minimo'] as $p) {
            $out .= "{$p['codigo_interno']};" . str_replace(';', ',', (string) $p['descricao'])
                . ";{$p['estoque_atual']};{$p['estoque_minimo']}\n";
        }
        return $out;
    }

    /** @return array{sql: string, params: array<string, string>} */
    private static function filtroDataSql(string $coluna, ?string $de, ?string $ate, string $prefix): array
    {
        $sql = '';
        $params = [];
        if ($de !== null && $de !== '') {
            $sql .= " AND {$coluna} >= :{$prefix}_de";
            $params["{$prefix}_de"] = $de . ' 00:00:00';
        }
        if ($ate !== null && $ate !== '') {
            $sql .= " AND {$coluna} <= :{$prefix}_ate";
            $params["{$prefix}_ate"] = $ate . ' 23:59:59';
        }
        return ['sql' => $sql, 'params' => $params];
    }
}
