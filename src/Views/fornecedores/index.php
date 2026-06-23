<div class="toolbar">
    <input type="search" id="busca-forn" class="input input-search" placeholder="Razão social ou CNPJ...">
    <button type="button" class="btn btn-primary" id="btn-novo-forn">+ Novo fornecedor</button>
</div>
<div id="tabela-fornecedores" data-endpoint="/fornecedores"></div>

<dialog id="modal-fornecedor">
    <form id="form-fornecedor">
        <h2>Fornecedor</h2>
        <input type="hidden" name="id" id="forn-id">
        <div class="form-grid">
            <label class="span-2">Razão social <input name="razao_social" required></label>
            <label>Nome fantasia <input name="nome_fantasia"></label>
            <label>CNPJ <input name="cnpj"></label>
            <label>Telefone <input name="telefone"></label>
            <label>E-mail <input type="email" name="email"></label>
        </div>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </footer>
    </form>
</dialog>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/fornecedores.js')) ?>"></script>
