<div id="cliente-detalhe" data-id="<?= (int)($cliente['id'] ?? 0) ?>">
    <p class="muted">Carregando...</p>
</div>
<section class="card">
    <div class="toolbar">
        <h2>Veículos</h2>
        <button type="button" class="btn btn-primary" id="btn-novo-veiculo">Adicionar veículo</button>
    </div>
    <div id="tabela-veiculos"></div>
</section>
<dialog id="modal-veiculo">
    <form id="form-veiculo">
        <h2>Veículo</h2>
        <input type="hidden" name="id" id="veiculo-id">
        <label>Placa <input name="placa" id="placa" required maxlength="8"></label>
        <label>Marca <input name="marca" required></label>
        <label>Modelo <input name="modelo" required></label>
        <label>Ano <input type="number" name="ano"></label>
        <label>KM <input type="number" name="km_atual" value="0"></label>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </footer>
    </form>
</dialog>
<script src="<?= htmlspecialchars($asset('assets/js/placa.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/cliente-show.js')) ?>"></script>
