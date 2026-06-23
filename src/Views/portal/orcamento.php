<?php $cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Orçamento') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">
    <script src="<?= htmlspecialchars($asset('assets/js/app.js')) ?>"></script>
    <script src="<?= htmlspecialchars($asset('assets/js/format.js')) ?>"></script>
</head>
<body class="login-page">
    <div class="login-shell" style="max-width:640px;grid-template-columns:1fr">
        <div class="login-card">
            <h2>Orçamento #<?= (int) $orcamento['numero'] ?></h2>
            <p class="muted"><?= htmlspecialchars($orcamento['cliente_nome']) ?> · <?= htmlspecialchars($orcamento['placa']) ?></p>
            <p>Status: <?= htmlspecialchars(match ($orcamento['status'] ?? '') {
                'enviado' => 'Aguardando sua resposta',
                'aprovado' => 'Aprovado',
                'reprovado' => 'Reprovado',
                default => ucfirst(str_replace('_', ' ', (string) ($orcamento['status'] ?? ''))),
            }) ?></p>
            <table class="table" style="margin:1rem 0">
                <thead><tr><th>Item</th><th>Qtd</th><th>Preço</th></tr></thead>
                <tbody>
                <?php foreach ($orcamento['itens'] as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['descricao']) ?></td>
                        <td><?= htmlspecialchars((string)$it['quantidade']) ?></td>
                        <td><?= number_format((float)$it['preco_unitario'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Total: <?= number_format((float)($orcamento['totais']['total'] ?? 0), 2, ',', '.') ?></strong></p>
            <?php if ($orcamento['status'] === 'enviado'): ?>
            <div class="toolbar" style="margin-top:1rem">
                <button type="button" class="btn btn-primary" id="btn-aprovar">Aprovar orçamento</button>
                <button type="button" class="btn" id="btn-reprovar">Reprovar</button>
            </div>
            <?php else: ?>
            <p class="muted">Este orçamento não está mais disponível para resposta.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
    const token = <?= json_encode($token) ?>;
    document.getElementById('btn-aprovar')?.addEventListener('click', async () => {
        const r = await fetch(`/portal/orcamento/${token}/aprovar`, { method: 'POST', headers: { Accept: 'application/json' } });
        const d = await r.json();
        alert(d.dados?.mensagem || d.erro || 'OK');
        location.reload();
    });
    document.getElementById('btn-reprovar')?.addEventListener('click', async () => {
        const obs = prompt('Motivo (opcional):') || '';
        const r = await fetch(`/portal/orcamento/${token}/reprovar`, {
            method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ observacao: obs }),
        });
        const d = await r.json();
        alert(d.dados?.mensagem || d.erro || 'OK');
        location.reload();
    });
    </script>
</body>
</html>
