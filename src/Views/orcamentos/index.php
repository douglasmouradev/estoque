<div class="toolbar">
    <input type="search" id="busca-orc" class="input input-search" placeholder="Número ou cliente...">
    <button type="button" class="btn btn-primary btn-write" id="btn-novo-orc">+ Novo orçamento</button>
</div>
<div id="tabela-orcamentos" data-endpoint="/orcamentos"></div>

<dialog id="modal-novo-orc">
    <form id="form-novo-orc">
        <h2>Novo orçamento</h2>
        <label>Cliente
            <input type="text" id="novo-busca-cliente" class="input" placeholder="Buscar cliente..." required autocomplete="off">
            <input type="hidden" id="novo-cliente-id">
        </label>
        <label>Veículo
            <select id="novo-veiculo-id" class="input" required></select>
        </label>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Criar</button>
        </footer>
    </form>
</dialog>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/orcamentos.js')) ?>"></script>
