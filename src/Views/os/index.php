<div class="toolbar">
    <input type="search" id="busca-os" class="input input-search" placeholder="Nº OS, cliente ou placa...">
    <button type="button" class="btn btn-primary btn-write" id="btn-nova-os">+ Nova OS</button>
</div>
<div id="tabela-os" data-endpoint="/os"></div>

<dialog id="modal-nova-os">
    <form id="form-nova-os">
        <h2>Nova ordem de serviço</h2>
        <label>Cliente
            <input type="text" id="os-busca-cliente" class="input" required autocomplete="off">
            <input type="hidden" id="os-cliente-id">
        </label>
        <label>Veículo<select id="os-veiculo-id" class="input" required></select></label>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Criar OS</button>
        </footer>
    </form>
</dialog>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/os.js')) ?>"></script>
