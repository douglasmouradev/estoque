const tabela = new DataTable('#tabela-fornecedores', {
    defaultSort: 'razao_social',
    mapRow(row) {
        return `<td>${escapeHtml(row.razao_social)}</td>
            <td>${escapeHtml(row.cnpj || '—')}</td>
            <td>${escapeHtml(row.telefone || '—')}</td>
            <td class="item-row-actions">
                <button class="btn btn-sm btn-ghost" data-edit="${row.id}">Editar</button>
                <button class="btn btn-sm btn-ghost" data-del="${row.id}">Excluir</button>
            </td>`;
    },
});
tabela.columns = ['razao_social', 'cnpj', 'telefone', ''];

const form = document.getElementById('form-fornecedor');
const modal = document.getElementById('modal-fornecedor');

document.getElementById('btn-novo-forn')?.addEventListener('click', () => {
    form.reset();
    document.getElementById('forn-id').value = '';
    modal.showModal();
});

document.getElementById('tabela-fornecedores')?.addEventListener('click', async (e) => {
    if (e.target.dataset?.del) {
        if (!await UI.confirm('Excluir fornecedor?')) return;
        await API.delete(`/fornecedores/${e.target.dataset.del}`);
        Toast.success('Excluído');
        tabela.load();
        return;
    }
    const id = e.target.dataset?.edit;
    if (!id) return;
    const rows = (await API.get('/fornecedores?per_page=100')).dados.itens;
    const f = rows.find((r) => String(r.id) === id);
    if (!f) return;
    document.getElementById('forn-id').value = f.id;
    ['razao_social','nome_fantasia','cnpj','telefone','email'].forEach((k) => {
        if (form.elements.namedItem(k)) form.elements.namedItem(k).value = f[k] || '';
    });
    modal.showModal();
});

form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(form));
    const fid = body.id;
    delete body.id;
    if (fid) await API.put(`/fornecedores/${fid}`, body);
    else await API.post('/fornecedores', body);
    Toast.success('Fornecedor salvo');
    modal.close();
    tabela.load();
});
