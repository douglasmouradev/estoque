<?php $cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'OS') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">
    <script src="<?= htmlspecialchars($asset('assets/js/format.js')) ?>"></script>
</head>
<body class="login-page">
    <div class="login-shell" style="max-width:640px;grid-template-columns:1fr">
        <div class="login-card">
            <h2>OS #<?= (int) $os['numero'] ?></h2>
            <p class="muted"><?= htmlspecialchars($os['cliente_nome']) ?> · <?= htmlspecialchars($os['placa']) ?></p>
            <p>Status: <strong><?= htmlspecialchars(str_replace('_', ' ', (string) $os['status'])) ?></strong></p>
            <?php
            $steps = ['aberta', 'em_andamento', 'aguardando_peca', 'finalizada'];
            $cur = array_search($os['status'], $steps, true);
            ?>
            <div class="stepper" style="margin:1rem 0">
                <?php foreach ($steps as $i => $s): ?>
                    <span class="step <?= $cur !== false && $i <= $cur ? 'done' : '' ?>"><?= htmlspecialchars(str_replace('_', ' ', $s)) ?></span>
                <?php endforeach; ?>
            </div>
            <h3>Itens</h3>
            <ul>
                <?php foreach ($os['itens'] as $it): ?>
                    <li><?= !empty($it['concluido']) ? '✓' : '○' ?> <?= htmlspecialchars($it['descricao']) ?> × <?= htmlspecialchars((string)$it['quantidade']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</body>
</html>
