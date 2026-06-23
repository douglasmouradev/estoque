const tabela = new DataTable('#tabela-servicos', {
    defaultSort: 'nome',
    columns: ['nome', 'descricao', 'preco_padrao', ''],
    mapRow(row) {
        return `<td>${escapeHtml(row.nome)}</td>
            <td>${escapeHtml(row.descricao || '—')}</td>
            <td>${Format.moeda(row.preco_padrao)}</td>
            <td><button class="btn btn-sm btn-ghost btn-write" data-edit="${row.id}">Editar</button></td>`;
    },
});

const form = document.getElementById('form-servico');
const modal = document.getElementById('modal-servico');

document.getElementById('btn-novo-servico')?.addEventListener('click', () => {
    form.reset();
    document.getElementById('servico-id').value = '';
    modal.showModal();
});

document.getElementById('tabela-servicos')?.addEventListener('click', async (e) => {
    const id = e.target.dataset?.edit;
    if (!id) return;
    const rows = (await API.get('/servicos?per_page=100')).dados.itens;
    const row = rows.find((r) => String(r.id) === String(id));
    if (!row) return;
    document.getElementById('servico-id').value = row.id;
    form.nome.value = row.nome;
    form.descricao.value = row.descricao || '';
    form.preco_padrao.value = row.preco_padrao;
    modal.showModal();
});

form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const body = Object.fromEntries(new FormData(form));
    const id = document.getElementById('servico-id').value;
    if (id) await API.put(`/servicos/${id}`, body);
    else await API.post('/servicos', body);
    Toast.success('Serviço salvo');
    modal.close();
    tabela.load();
});
