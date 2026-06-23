<div class="toolbar">
    <a href="/financeiro/exportar" class="btn btn-ghost">Exportar CSV</a>
</div>
<div class="stat-card" style="margin-bottom:1rem">
    <div class="stat-label">Total pendente</div>
    <div class="stat-value" id="fin-total-pendente">—</div>
</div>
<div id="tabela-financeiro" data-endpoint="/financeiro"></div>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/financeiro.js')) ?>"></script>
