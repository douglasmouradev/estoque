<div class="toolbar">
    <input type="search" id="busca-clientes" placeholder="Buscar nome ou CPF/CNPJ... (/) " class="input input-search" aria-label="Buscar clientes">
    <button type="button" class="btn btn-primary btn-write" id="btn-novo-cliente">+ Novo cliente</button>
</div>
<div id="tabela-clientes" data-endpoint="/clientes"></div>

<dialog id="modal-cliente">
    <form id="form-cliente">
        <h2>Cliente</h2>
        <input type="hidden" name="id" id="cliente-id">
        <div class="form-grid">
            <label>Nome <input name="nome" required></label>
            <label>CPF/CNPJ <input name="cpf_cnpj" required></label>
            <label>Telefone <input name="telefone"></label>
            <label>E-mail <input type="email" name="email"></label>
            <label>CEP <input name="cep"></label>
            <label>Cidade <input name="cidade"></label>
            <label>UF <input name="uf" maxlength="2"></label>
            <label>Bairro <input name="bairro"></label>
            <label>Logradouro <input name="logradouro"></label>
            <label>Número <input name="numero"></label>
            <label>Complemento <input name="complemento"></label>
        </div>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </footer>
    </form>
</dialog>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/clientes.js')) ?>"></script>
