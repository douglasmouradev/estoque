<?php $d = $dados ?? []; ?>
<div class="toolbar btn-write" style="margin-bottom:1rem">
    <label>De <input type="date" id="rel-de" class="input" value="<?= htmlspecialchars($de ?? date('Y-m-01')) ?>"></label>
    <label>Até <input type="date" id="rel-ate" class="input" value="<?= htmlspecialchars($ate ?? date('Y-m-d')) ?>"></label>
    <button type="button" class="btn" id="btn-filtrar-rel">Filtrar</button>
    <a href="/relatorios/exportar?tipo=estoque" class="btn btn-ghost" id="btn-export-estoque">Exportar estoque</a>
    <a href="/relatorios/exportar?tipo=mecanicos" class="btn btn-ghost" id="btn-export-mec">Exportar mecânicos</a>
</div>

<div class="grid-3">
    <div class="stat-card">
        <div class="stat-label">OS abertas</div>
        <div class="stat-value"><?= (int) ($d['os_abertas'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Orçamentos aguardando</div>
        <div class="stat-value"><?= (int) ($d['orcamentos_aguardando'] ?? 0) ?></div>
    </div>
    <div class="stat-card <?= ($d['financeiro_pendente'] ?? 0) > 0 ? 'warning' : '' ?>">
        <div class="stat-label">A receber</div>
        <div class="stat-value">R$ <?= number_format((float) ($d['financeiro_pendente'] ?? 0), 2, ',', '.') ?></div>
    </div>
</div>

<div class="grid-2" style="margin-top:1rem">
    <section class="card">
        <div class="card-header"><h2>Faturamento no período</h2></div>
        <p><strong>R$ <?= number_format((float) ($d['faturamento_periodo']['total'] ?? 0), 2, ',', '.') ?></strong>
        — <?= (int) ($d['faturamento_periodo']['os_finalizadas'] ?? 0) ?> OS finalizadas</p>
        <canvas id="chart-faturamento" height="120" aria-label="Gráfico faturamento"></canvas>
    </section>
    <section class="card">
        <div class="card-header"><h2>Horas por mecânico</h2></div>
        <canvas id="chart-mecanicos" height="120" aria-label="Gráfico mecânicos"></canvas>
    </section>
</div>

<div class="grid-2" style="margin-top:1rem">
    <section class="card">
        <div class="card-header"><h2>Peças abaixo do mínimo</h2></div>
        <?php if (empty($d['pecas_abaixo_minimo'])): ?>
            <p class="muted empty-state-inner">Nenhuma peça abaixo do mínimo.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Código</th><th>Descrição</th><th>Saldo</th><th>Mín.</th></tr></thead>
                    <tbody>
                    <?php foreach ($d['pecas_abaixo_minimo'] as $p): ?>
                        <tr>
                            <td><a href="/estoque/<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['codigo_interno']) ?></a></td>
                            <td><?= htmlspecialchars($p['descricao']) ?></td>
                            <td class="text-danger"><?= htmlspecialchars((string)$p['estoque_atual']) ?></td>
                            <td><?= htmlspecialchars((string)$p['estoque_minimo']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <section class="card">
        <div class="card-header"><h2>Produtividade</h2></div>
        <?php if (empty($d['produtividade_mecanicos'])): ?>
            <p class="muted">Sem horas registradas no período.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($d['produtividade_mecanicos'] as $m): ?>
                    <li><?= htmlspecialchars($m['nome']) ?> — <?= htmlspecialchars((string)$m['total_horas']) ?>h (<?= (int)$m['os_count'] ?> OS)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="<?= htmlspecialchars($asset('assets/js/relatorios.js')) ?>"></script>
<script>
window.__relData = <?= json_encode([
    'faturamento' => $d['faturamento_periodo']['total'] ?? 0,
    'mecanicos' => $d['produtividade_mecanicos'] ?? [],
], JSON_UNESCAPED_UNICODE) ?>;
</script>
