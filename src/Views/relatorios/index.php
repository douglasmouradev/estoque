<?php $d = $dados ?? []; ?>
<div class="grid-3">
    <div class="stat-card">
        <div class="stat-label">OS abertas</div>
        <div class="stat-value"><?= (int) ($d['os_abertas'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Orçamentos aguardando cliente</div>
        <div class="stat-value"><?= (int) ($d['orcamentos_aguardando'] ?? 0) ?></div>
    </div>
    <div class="stat-card <?= ($d['financeiro_pendente'] ?? 0) > 0 ? 'warning' : '' ?>">
        <div class="stat-label">Financeiro pendente</div>
        <div class="stat-value">R$ <?= number_format((float) ($d['financeiro_pendente'] ?? 0), 2, ',', '.') ?></div>
    </div>
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
        <div class="card-header"><h2>Peças paradas</h2></div>
        <?php if (empty($d['pecas_paradas'])): ?>
            <p class="muted empty-state-inner">Nenhuma peça parada no período.</p>
        <?php else: ?>
            <ul class="muted">
                <?php foreach (array_slice($d['pecas_paradas'], 0, 15) as $p): ?>
                    <li><a href="/estoque/<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['codigo_interno']) ?></a> — <?= htmlspecialchars($p['descricao']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
