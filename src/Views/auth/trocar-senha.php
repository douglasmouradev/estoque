<?php $cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trocar senha</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">
    <script src="<?= htmlspecialchars($asset('assets/js/app.js')) ?>"></script>
    <script src="<?= htmlspecialchars($asset('assets/js/api.js')) ?>"></script>
</head>
<body class="login-page">
    <form class="login-card" id="form-senha" style="max-width:400px;margin:auto">
        <h2>Trocar senha</h2>
        <p class="muted">Por segurança, defina uma nova senha antes de continuar.</p>
        <label>Nova senha <input type="password" name="senha" required minlength="6" class="input"></label>
        <label>Confirmar <input type="password" name="senha_confirmacao" required minlength="6" class="input"></label>
        <button type="submit" class="btn btn-primary" style="width:100%">Salvar e continuar</button>
    </form>
    <script>
    document.getElementById('form-senha').addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = Object.fromEntries(new FormData(e.target));
        const res = await API.post('/trocar-senha', body);
        if (res.sucesso) location.href = res.dados?.redirect || '/';
    });
    </script>
</body>
</html>
