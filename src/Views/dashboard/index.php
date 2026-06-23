<?php $qtdAlertas = count($alertas_estoque ?? []); ?>

<div class="grid-3">

    <div class="stat-card <?= $qtdAlertas > 0 ? 'warning' : '' ?>">

        <div class="stat-label">Estoque mínimo</div>

        <div class="stat-value"><?= $qtdAlertas ?></div>

        <p class="muted" style="font-size:0.8rem;margin-top:0.35rem">Peças abaixo do mínimo</p>

    </div>

    <div class="stat-card">

        <div class="stat-label">OS abertas</div>

        <div class="stat-value"><?= (int) ($os_abertas ?? 0) ?></div>

        <p class="muted" style="font-size:0.8rem;margin-top:0.35rem">Em andamento na oficina</p>

    </div>

    <div class="stat-card">

        <div class="stat-label">Aguardando cliente</div>

        <div class="stat-value"><?= (int) ($orcamentos_aguardando ?? 0) ?></div>

        <p class="muted" style="font-size:0.8rem;margin-top:0.35rem">Orçamentos enviados</p>

    </div>

</div>



<div class="grid-2">

    <section class="card">

        <div class="card-header">

            <h2>Alertas de estoque</h2>

            <?php if ($qtdAlertas > 0): ?><span class="badge badge-danger"><?= $qtdAlertas ?></span><?php endif; ?>

        </div>

        <?php if (empty($alertas_estoque)): ?>

            <div class="empty-state"><p>Estoque dentro dos limites configurados.</p></div>

        <?php else: ?>

            <div class="table-wrap">

                <table class="table">

                    <thead><tr><th>Código</th><th>Descrição</th><th>Saldo</th><th>Mín.</th></tr></thead>

                    <tbody>

                    <?php foreach ($alertas_estoque as $p): ?>

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

        <div class="card-header"><h2>Acesso rápido</h2></div>

        <ul class="quick-links">

            <li><a href="/estoque">Gerenciar estoque</a></li>

            <li><a href="/estoque/paradas">Peças paradas</a></li>

            <?php if (\App\Core\Auth::canAccessPath('/orcamentos')): ?>

            <li><a href="/orcamentos">Orçamentos</a></li>

            <?php endif; ?>

            <li><a href="/os">Ordens de serviço</a></li>

            <?php if (\App\Core\Auth::canAccessPath('/clientes')): ?>

            <li><a href="/clientes">Clientes</a></li>

            <?php endif; ?>

            <?php if ($podeEscrever ?? false): ?>

            <li><a href="/fornecedores">Fornecedores</a></li>

            <?php endif; ?>

        </ul>

    </section>

</div>

