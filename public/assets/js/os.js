new DataTable('#tabela-os', {
    defaultSort: 'created_at',
    mapRow(row) {
        return `<td><a href="/os/${row.id}">#${row.numero}</a></td>
            <td>${escapeHtml(row.cliente_nome)}</td>
            <td>${escapeHtml(row.placa || '—')}</td>
            <td>${Format.statusOs(row.status)}</td>
            <td>${Format.data(row.created_at)}</td>`;
    },
}).columns = ['numero', 'cliente_nome', 'placa', 'status', 'created_at'];

document.getElementById('btn-nova-os')?.addEventListener('click', (e) => {
    e.preventDefault();
    document.getElementById('modal-nova-os')?.showModal();
});

document.getElementById('form-nova-os')?.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const res = await API.post('/os', {
        cliente_id: parseInt(document.getElementById('os-cliente-id').value, 10),
        veiculo_id: parseInt(document.getElementById('os-veiculo-id').value, 10),
    });
    location.href = `/os/${res.dados.id}`;
});

const osBusca = document.getElementById('os-busca-cliente');
if (osBusca) {
    UI.autocomplete(osBusca, async (q) => {
        const r = await API.get(`/clientes/buscar?q=${encodeURIComponent(q)}`);
        return (r.dados || []).map((c) => ({ ...c, label: c.nome }));
    }, async (c) => {
        osBusca.value = c.nome;
        document.getElementById('os-cliente-id').value = c.id;
        const vr = await API.get(`/veiculos/cliente/${c.id}?per_page=100`);
        document.getElementById('os-veiculo-id').innerHTML = (vr.dados?.itens || []).map((v) =>
            `<option value="${v.id}">${escapeHtml(v.placa)}</option>`).join('');
    });
}
