<?php $cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">
    <script src="<?= htmlspecialchars($asset('assets/js/app.js')) ?>"></script>
    <script src="<?= htmlspecialchars($asset('assets/js/api.js')) ?>"></script>
</head>
<body class="login-page">
    <form class="login-card" id="form-esqueci" style="max-width:400px;margin:auto">
        <h2>Esqueci minha senha</h2>
        <p class="muted">Informe seu e-mail para receber o link de redefinição.</p>
        <label>E-mail <input type="email" name="email" required class="input"></label>
        <button type="submit" class="btn btn-primary" style="width:100%">Enviar</button>
        <p style="margin-top:1rem"><a href="/login">Voltar ao login</a></p>
    </form>
    <script>
    document.getElementById('form-esqueci').addEventListener('submit', async (e) => {
        e.preventDefault();
        const res = await API.post('/esqueci-senha', Object.fromEntries(new FormData(e.target)));
        alert(res.dados?.mensagem || res.erro || 'Verifique seu e-mail.');
        if (res.sucesso) location.href = '/login';
    });
    </script>
</body>
</html>
