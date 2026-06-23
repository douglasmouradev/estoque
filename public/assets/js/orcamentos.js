const tabela = new DataTable('#tabela-orcamentos', {
    defaultSort: 'created_at',
    mapRow(row) {
        return `<td><a href="/orcamentos/${row.id}">#${row.numero}</a></td>
            <td>${escapeHtml(row.cliente_nome)}</td>
            <td>${escapeHtml(row.placa || '—')}</td>
            <td>${Format.statusOrcamento(row.status)}</td>
            <td>${Format.data(row.created_at)}</td>`;
    },
});
tabela.columns = ['numero', 'cliente_nome', 'placa', 'status', 'created_at'];

document.getElementById('btn-novo-orc')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('modal-novo-orc')?.showModal();
});

document.getElementById('form-novo-orc')?.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const clienteId = document.getElementById('novo-cliente-id').value;
    const veiculoId = document.getElementById('novo-veiculo-id').value;
    if (!clienteId || !veiculoId) {
        Toast.error('Selecione cliente e veículo');
        return;
    }
    const res = await API.post('/orcamentos', { cliente_id: parseInt(clienteId, 10), veiculo_id: parseInt(veiculoId, 10) });
    location.href = `/orcamentos/${res.dados.id}`;
});

const buscaCli = document.getElementById('novo-busca-cliente');
if (buscaCli) {
    UI.autocomplete(buscaCli, async (q) => {
        const r = await API.get(`/clientes/buscar?q=${encodeURIComponent(q)}`);
        return (r.dados || []).map((c) => ({ ...c, label: `${c.nome} (${c.cpf_cnpj})` }));
    }, async (c) => {
        buscaCli.value = c.nome;
        document.getElementById('novo-cliente-id').value = c.id;
        const vr = await API.get(`/veiculos/cliente/${c.id}?per_page=100`);
        const sel = document.getElementById('novo-veiculo-id');
        sel.innerHTML = (vr.dados?.itens || []).map((v) =>
            `<option value="${v.id}">${escapeHtml(v.placa)} — ${escapeHtml(v.marca)}</option>`
        ).join('');
    });
}
