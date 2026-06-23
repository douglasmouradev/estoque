<div class="toolbar">
    <input type="search" id="busca-pecas" placeholder="Código, OEM ou descrição..." class="input input-search">
    <label class="checkbox"><input type="checkbox" id="filtro-minimo"> Abaixo do mínimo</label>
    <a href="/estoque/paradas" class="btn btn-ghost">Peças paradas</a>
    <a href="/config#categorias" class="btn btn-ghost btn-write">Categorias</a>
    <button type="button" class="btn btn-ghost btn-write" id="btn-importar">Importar CSV</button>
    <button type="button" class="btn btn-primary btn-write" id="btn-nova-peca">+ Nova peça</button>
</div>
<div id="tabela-estoque" data-endpoint="/estoque"></div>

<dialog id="modal-peca">
    <form id="form-peca">
        <h2>Peça</h2>
        <input type="hidden" name="id" id="peca-id">
        <div class="form-grid">
            <label>Código interno <input name="codigo_interno" required></label>
            <label>Código OEM <input name="codigo_oem"></label>
            <label class="span-2">Descrição <input name="descricao" required></label>
            <label>Unidade
                <select name="unidade">
                    <option value="un">Unidade</option><option value="lt">Litro</option>
                    <option value="kg">Kg</option><option value="m">Metro</option>
                </select>
            </label>
            <label>Categoria <select name="categoria_id" id="categoria_id"><option value="">—</option></select></label>
            <label>Marca <input name="marca"></label>
            <label>Localização <input name="localizacao" placeholder="Ex: A3"></label>
            <label>Estoque mínimo <input type="number" step="0.001" name="estoque_minimo" value="0"></label>
            <label id="campo-estoque-inicial">Saldo inicial <input type="number" step="0.001" name="estoque_inicial" value="0" min="0"></label>
            <label>Preço venda <input type="number" step="0.01" name="preco_venda" value="0"></label>
        </div>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </footer>
    </form>
</dialog>
<input type="file" id="csv-file" accept=".csv" hidden>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/estoque.js')) ?>"></script>
