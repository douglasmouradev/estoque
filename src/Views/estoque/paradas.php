<section class="card">
    <div class="card-header">
        <h2>Peças sem movimentação (<?= (int) $dias ?> dias)</h2>
        <a href="/estoque" class="btn btn-ghost">← Voltar ao estoque</a>
    </div>
    <?php if (empty($pecas)): ?>
        <div class="empty-state"><p>Nenhuma peça parada no período.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Código</th><th>Descrição</th><th>Local</th><th>Saldo</th><th>Última mov.</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pecas as $p): ?>
                    <tr>
                        <td><a href="/estoque/<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['codigo_interno']) ?></a></td>
                        <td><?= htmlspecialchars($p['descricao']) ?></td>
                        <td><?= htmlspecialchars($p['localizacao'] ?? '—') ?></td>
                        <td><?= htmlspecialchars((string)($p['estoque_atual'] ?? 0)) ?></td>
                        <td><?= $p['ultima_movimentacao'] ? htmlspecialchars(date('d/m/Y', strtotime($p['ultima_movimentacao']))) : 'Nunca' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
