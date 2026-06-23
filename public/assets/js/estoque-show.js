(async function () {
    const root = document.getElementById('peca-detalhe');
    const id = root?.dataset.id;
    if (!id) return;
    let fornecedoresLista = [];

    async function load() {
        const [pecaRes, fornRes] = await Promise.all([
            API.get(`/estoque/${id}`),
            API.get('/fornecedores/todos'),
        ]);
        const p = pecaRes.dados;
        fornecedoresLista = fornRes.dados || [];
        const hist = p.historico?.itens || [];
        const pode = !document.body.classList.contains('somente-leitura');

        root.innerHTML = `
        <div class="grid-2">
            <div class="card">
                <div class="card-header"><h2>${escapeHtml(p.descricao)}</h2>
                    <span class="badge badge-neutral">${escapeHtml(p.unidade)}</span></div>
                <p><strong>Código:</strong> ${escapeHtml(p.codigo_interno)} · <strong>OEM:</strong> ${escapeHtml(p.codigo_oem || '—')}</p>
                <p><strong>Marca:</strong> ${escapeHtml(p.marca || '—')} · <strong>Categoria:</strong> ${escapeHtml(p.categoria_nome || '—')}</p>
                <p><strong>Saldo:</strong> <span class="${parseFloat(p.estoque_atual) <= parseFloat(p.estoque_minimo) ? 'text-danger' : ''}">${p.estoque_atual}</span> (mín: ${p.estoque_minimo})</p>
                <p><strong>Preço venda:</strong> ${Format.moeda(p.preco_venda)} · <strong>Local:</strong> ${escapeHtml(p.localizacao || '—')}</p>
            </div>
            ${pode ? `<form id="form-mov" class="card form-grid">
                <h3>Movimentação</h3>
                <label>Motivo<select name="motivo">
                    <option value="compra">Compra</option><option value="devolucao">Devolução</option>
                    <option value="ajuste">Ajuste (saída)</option>
                </select></label>
                <label>Quantidade<input type="number" step="0.001" name="quantidade" required></label>
                <label class="span-2">Observação<input name="observacao"></label>
                <button class="btn btn-primary btn-write">Registrar</button>
            </form>` : ''}
        </div>
        <section class="card">
            <div class="card-header"><h3>Fornecedores</h3></div>
            <div class="table-wrap"><table class="table"><thead><tr><th>Fornecedor</th><th>Preço</th><th>Prazo</th></tr></thead>
            <tbody>${(p.fornecedores || []).map(f => `<tr>
                <td>${escapeHtml(f.razao_social)}</td><td>${Format.moeda(f.preco_compra)}</td><td>${f.prazo_entrega_dias} dias</td></tr>`).join('') || '<tr><td colspan="3" class="muted">Nenhum fornecedor</td></tr>'}</tbody></table></div>
            ${pode ? `<form id="form-forn" class="form-grid" style="margin-top:1rem">
                <label>Fornecedor<select name="fornecedor_id" required>${fornecedoresLista.map(f => `<option value="${f.id}">${escapeHtml(f.razao_social)}</option>`)}</select></label>
                <label>Preço compra<input type="number" step="0.01" name="preco_compra" required></label>
                <label>Prazo (dias)<input type="number" name="prazo_entrega_dias" value="0"></label>
                <label class="checkbox"><input type="checkbox" name="preferencial"> Preferencial</label>
                <button class="btn btn-write">Vincular</button>
            </form>` : ''}
        </section>
        <section class="card">
            <div class="card-header"><h3>Histórico</h3></div>
            <div class="table-wrap"><table class="table"><thead><tr><th>Data</th><th>Tipo</th><th>Qtd</th><th>Motivo</th><th>Saldo</th></tr></thead>
            <tbody>${hist.map(h => `<tr>
                <td>${Format.dataHora(h.created_at)}</td><td>${h.tipo}</td><td>${h.quantidade}</td>
                <td>${h.motivo}</td><td>${h.saldo_apos}</td></tr>`).join('')}</tbody></table></div>
        </section>`;

        document.getElementById('form-mov')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await API.post(`/estoque/${id}/movimentar`, Object.fromEntries(new FormData(e.target)));
            Toast.success('Movimentação registrada');
            load();
        });
        document.getElementById('form-forn')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            const body = Object.fromEntries(fd);
            body.preferencial = e.target.elements.preferencial.checked;
            await API.post(`/estoque/${id}/fornecedor`, body);
            Toast.success('Fornecedor vinculado');
            load();
        });
    }
    load();
})();
