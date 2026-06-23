<div class="toolbar">
    <input type="search" id="busca-servicos" class="input input-search" placeholder="Buscar serviço...">
    <button type="button" class="btn btn-primary btn-write" id="btn-novo-servico">+ Novo serviço</button>
</div>
<div id="tabela-servicos" data-endpoint="/servicos"></div>

<dialog id="modal-servico">
    <form id="form-servico">
        <h2>Serviço</h2>
        <input type="hidden" id="servico-id">
        <label>Nome <input name="nome" required class="input"></label>
        <label>Descrição <input name="descricao" class="input"></label>
        <label>Preço padrão <input type="number" step="0.01" name="preco_padrao" value="0" class="input"></label>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </footer>
    </form>
</dialog>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/servicos.js')) ?>"></script>
