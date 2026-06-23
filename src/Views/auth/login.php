<?php $cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1'; ?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Entrar — Oficina</title>

    <?php require dirname(__DIR__) . '/layouts/partials/favicon.php'; ?>

    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">

    <script src="<?= htmlspecialchars($asset('assets/js/app.js')) ?>"></script>

    <script src="<?= htmlspecialchars($asset('assets/js/format.js')) ?>"></script>

    <script src="<?= htmlspecialchars($asset('assets/js/ui.js')) ?>"></script>

    <script src="<?= htmlspecialchars($asset('assets/js/api.js')) ?>"></script>

</head>

<body class="login-page">

    <div class="login-shell">

        <div class="login-hero">

            <p class="tagline">Oficina mecânica</p>

            <h1>Controle do estoque e serviços</h1>

            <p>Orçamentos, peças e OS em um só sistema.</p>

        </div>

        <form class="login-card" id="form-login">

            <img src="<?= htmlspecialchars($asset('assets/img/logo-oficina.png')) ?>" alt="Oficina" class="brand-logo brand-logo-lg" width="44" height="44">

            <h2>Entrar</h2>

            <p class="muted">Use seu e-mail e senha de acesso</p>

            <?php if ($msg = \App\Core\Session::pullFlash('erro')): ?>

                <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>

            <?php endif; ?>

            <label>E-mail

                <input type="email" name="email" required autocomplete="username">

            </label>

            <label>Senha

                <div class="input-password-wrap">

                    <input type="password" name="password" id="login-password" required autocomplete="current-password">

                    <button type="button" class="btn-toggle-password" id="toggle-password" aria-label="Mostrar senha">👁</button>

                </div>

            </label>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.25rem">Acessar sistema</button>

            <p style="margin-top:0.75rem;text-align:center;font-size:0.85rem"><a href="/esqueci-senha">Esqueci minha senha</a></p>

        </form>

    </div>

    <div id="toast-container"></div>

    <div id="spinner" class="spinner hidden"></div>

    <script>

    document.getElementById('toggle-password')?.addEventListener('click', () => {

        const inp = document.getElementById('login-password');

        const show = inp.type === 'password';

        inp.type = show ? 'text' : 'password';

        document.getElementById('toggle-password').setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');

    });

    document.getElementById('form-login').addEventListener('submit', async (e) => {

        e.preventDefault();

        const fd = new FormData(e.target);

        const res = await API.post('/login', Object.fromEntries(fd));

        if (res.sucesso) location.href = res.dados?.redirect || '/';

    });

    </script>

</body>

</html>

