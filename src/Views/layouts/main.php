<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$navActive = static function (string $prefix) use ($path): string {

    if ($prefix === '/' && $path === '/') {

        return ' active';

    }

    if ($prefix !== '/' && str_starts_with($path, $prefix)) {

        return ' active';

    }

    return '';

};

$navLabels = [

    'clientes' => 'Clientes',

    'estoque' => 'Estoque',

    'orcamentos' => 'Orçamentos',

    'os' => 'Ordens de Serviço',

    'fornecedores' => 'Fornecedores',

    'usuarios' => 'Usuários',

    'config' => 'Configurações',

    'paradas' => 'Peças paradas',

    'relatorios' => 'Relatórios',

    'servicos' => 'Serviços',

    'busca' => 'Busca',

];

$segments = array_values(array_filter(explode('/', trim($path, '/'))));

$crumbs = [];

$acc = '';

foreach ($segments as $seg) {

    if (ctype_digit($seg)) {

        $crumbs[] = ['label' => 'Detalhe', 'url' => null];

        continue;

    }

    $acc .= '/' . $seg;

    $crumbs[] = ['label' => $navLabels[$seg] ?? ucfirst(str_replace('-', ' ', $seg)), 'url' => $acc];

}

$backUrl = null;

if (count($segments) > 1) {

    $parent = $segments;

    $last = array_pop($parent);

    if (ctype_digit($last)) {

        $backUrl = $parent ? '/' . implode('/', $parent) : null;

    } else {

        $backUrl = $parent ? '/' . implode('/', $parent) : null;

    }

}

$cssVer = @filemtime(dirname(__DIR__, 3) . '/public/assets/css/app.css') ?: '1';

$pageSubtitle = $subtitulo ?? 'Gestão de estoque, orçamentos e ordens de serviço';

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($titulo ?? 'Oficina') ?> — Estoque</title>

    <?php require __DIR__ . '/partials/favicon.php'; ?>

    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">

    <style>

        body{margin:0;font-family:system-ui,sans-serif}

        .app{display:flex;min-height:100vh}

        .sidebar{width:248px;flex-shrink:0}

        .content{flex:1;min-width:0}

        .nav-icon{width:18px;height:18px;flex-shrink:0}

    </style>

    <link rel="stylesheet" href="<?= htmlspecialchars($asset('assets/css/app.css')) ?>?v=<?= (int) $cssVer ?>">

    <script src="<?= htmlspecialchars($asset('assets/js/app.js')) ?>"></script>

    <script src="<?= htmlspecialchars($asset('assets/js/format.js')) ?>"></script>

    <script src="<?= htmlspecialchars($asset('assets/js/masks.js')) ?>"></script>

    <script src="<?= htmlspecialchars($asset('assets/js/ui.js')) ?>"></script>

    <script src="<?= htmlspecialchars($asset('assets/js/api.js')) ?>"></script>

</head>

<body class="<?= $podeEscrever ? '' : 'somente-leitura' ?>" data-perfil="<?= htmlspecialchars($perfilAtual) ?>">

    <div class="sidebar-backdrop" aria-hidden="true"></div>

    <div class="app">

        <aside class="sidebar" id="sidebar">

            <div class="brand">

                <img src="<?= htmlspecialchars($asset('assets/img/logo-oficina.png')) ?>" alt="" class="brand-logo" width="36" height="36">

                <div class="brand-text">

                    <strong>Oficina</strong>

                    <span>Estoque & OS</span>

                </div>

            </div>

            <nav>

                <a href="/" class="nav-link<?= $navActive('/') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>

                    <span>Painel</span>

                </a>

                <?php if (\App\Core\Auth::canAccessPath('/clientes')): ?>

                <a href="/clientes" class="nav-link<?= $navActive('/clientes') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>

                    <span>Clientes</span>

                </a>

                <?php endif; ?>

                <a href="/estoque" class="nav-link<?= $navActive('/estoque') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>

                    <span>Estoque</span>

                </a>

                <?php if (\App\Core\Auth::canAccessPath('/orcamentos')): ?>

                <a href="/orcamentos" class="nav-link<?= $navActive('/orcamentos') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>

                    <span>Orçamentos</span>

                </a>

                <?php endif; ?>

                <a href="/os" class="nav-link<?= $navActive('/os') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>

                    <span>Ordens de Serviço</span>

                </a>

                <?php if (\App\Core\Auth::canAccessPath('/relatorios')): ?>

                <a href="/relatorios" class="nav-link<?= $navActive('/relatorios') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 5-9"/></svg>

                    <span>Relatórios</span>

                </a>

                <?php endif; ?>

                <?php if ($podeEscrever): ?>

                <a href="/servicos" class="nav-link<?= $navActive('/servicos') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2v20M2 12h20"/></svg>

                    <span>Serviços</span>

                </a>

                <?php endif; ?>

                <?php if ($podeEscrever): ?>

                <a href="/fornecedores" class="nav-link<?= $navActive('/fornecedores') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9h18v10H3z"/><path d="M8 9V5h8v4"/></svg>

                    <span>Fornecedores</span>

                </a>

                <?php endif; ?>

                <?php if ($isAdmin): ?>

                <a href="/usuarios" class="nav-link<?= $navActive('/usuarios') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>

                    <span>Usuários</span>

                </a>

                <a href="/config" class="nav-link<?= $navActive('/config') ?>">

                    <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>

                    <span>Configurações</span>

                </a>

                <?php endif; ?>

            </nav>

            <div class="user-card">

                <div class="user-card-top">

                    <div class="user-avatar"><?= htmlspecialchars($iniciais) ?></div>

                    <div class="user-info">

                        <strong><?= htmlspecialchars($usuarioNome) ?></strong>

                        <small><?= htmlspecialchars($usuarioPerfil) ?></small>

                    </div>

                </div>

                <form action="/logout" method="post" class="logout-form">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit"><span>Sair</span></button>
                </form>

            </div>

        </aside>

        <main class="content">

            <header class="page-header">

                <div class="page-header-main">

                    <button type="button" class="btn btn-ghost btn-icon" id="menu-toggle" aria-label="Abrir menu">

                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>

                    </button>

                    <?php if ($backUrl): ?>

                    <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-ghost btn-icon btn-back" aria-label="Voltar">

                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>

                    </a>

                    <?php endif; ?>

                    <div>

                        <?php if (!empty($crumbs)): ?>

                        <nav class="breadcrumb" aria-label="Navegação">

                            <a href="/">Painel</a>

                            <?php foreach ($crumbs as $c): ?>

                                <span class="breadcrumb-sep">/</span>

                                <?php if ($c['url']): ?>

                                    <a href="<?= htmlspecialchars($c['url']) ?>"><?= htmlspecialchars($c['label']) ?></a>

                                <?php else: ?>

                                    <span><?= htmlspecialchars($c['label']) ?></span>

                                <?php endif; ?>

                            <?php endforeach; ?>

                        </nav>

                        <?php endif; ?>

                        <h1><?= htmlspecialchars($titulo ?? '') ?></h1>

                        <p class="subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>

                    </div>

                </div>

                <div class="page-header-actions">
                    <div class="busca-global-wrap">
                        <input type="search" id="busca-global" class="input input-search" placeholder="Buscar cliente, placa, peça, OS..." autocomplete="off">
                        <div id="busca-resultados" class="busca-resultados hidden"></div>
                    </div>
                </div>

            </header>

            <div class="page-body">

                <?= $content ?>

            </div>

        </main>

    </div>

    <div id="toast-container"></div>

    <div id="spinner" class="spinner hidden" aria-hidden="true"></div>



    <dialog id="modal-confirm">

        <form method="dialog">

            <h2 data-confirm-title>Confirmar</h2>

            <p data-confirm-msg></p>

            <footer>

                <button type="button" class="btn" data-confirm-cancel>Cancelar</button>

                <button type="button" class="btn btn-primary" data-confirm-ok>Confirmar</button>

            </footer>

        </form>

    </dialog>

    <dialog id="modal-prompt">

        <form method="dialog">

            <h2 data-prompt-title>Informe</h2>

            <p data-prompt-msg></p>

            <input type="text" data-prompt-input class="input" style="width:100%">

            <footer>

                <button type="button" class="btn" data-prompt-cancel>Cancelar</button>

                <button type="button" class="btn btn-primary" data-prompt-ok>OK</button>

            </footer>

        </form>

    </dialog>

    <dialog id="modal-relatorio" class="modal-wide">

        <form method="dialog">

            <h2 data-relatorio-title>Relatório</h2>

            <div data-relatorio-body class="relatorio-body"></div>

            <footer>

                <button type="button" class="btn btn-primary" data-relatorio-ok>Fechar</button>

            </footer>

        </form>

    </dialog>

    <dialog id="modal-pdf" class="modal-pdf">

        <form method="dialog">

            <header class="modal-pdf-header">

                <h2 data-pdf-title>PDF</h2>

                <button type="button" class="btn btn-ghost btn-sm" data-close>Fechar</button>

            </header>

            <iframe data-pdf-frame title="Visualização PDF" class="pdf-frame"></iframe>

        </form>

    </dialog>



    <script src="<?= htmlspecialchars($asset('assets/js/busca.js')) ?>"></script>

</body>

</html>

