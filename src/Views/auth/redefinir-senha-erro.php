<?php $cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Link inválido</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">
</head>
<body class="login-page">
    <div class="login-card" style="max-width:400px;margin:auto">
        <h2>Link expirado</h2>
        <p class="muted"><a href="/esqueci-senha">Solicitar novo link</a></p>
    </div>
</body>
</html>
