<?php $cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nova senha</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">
    <script src="<?= htmlspecialchars($asset('assets/js/app.js')) ?>"></script>
    <script src="<?= htmlspecialchars($asset('assets/js/api.js')) ?>"></script>
</head>
<body class="login-page">
    <form class="login-card" id="form-reset" style="max-width:400px;margin:auto">
        <h2>Nova senha</h2>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
        <label>Nova senha <input type="password" name="senha" required minlength="6" class="input"></label>
        <label>Confirmar <input type="password" name="senha_confirmacao" required minlength="6" class="input"></label>
        <button type="submit" class="btn btn-primary" style="width:100%">Salvar</button>
    </form>
    <script>
    document.getElementById('form-reset').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await API.post('/redefinir-senha', Object.fromEntries(new FormData(e.target)));
        if (res.sucesso) location.href = res.dados?.redirect || '/login';
    });
    </script>
</body>
</html>
