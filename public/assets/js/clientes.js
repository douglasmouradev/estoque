const tabela = new DataTable('#tabela-clientes', {
    defaultSort: 'nome',
    emptyMessage: 'Nenhum cliente cadastrado',
    emptyCta: {
        label: '+ Cadastrar primeiro cliente',
        onClick: () => document.getElementById('btn-novo-cliente')?.click(),
    },
    mapRow(row) {
        return `<td><a href="/clientes/${row.id}">${escapeHtml(row.nome)}</a></td>
            <td>${escapeHtml(row.cpf_cnpj)}</td>
            <td>${escapeHtml(row.telefone || '—')}</td>
            <td>${escapeHtml(row.cidade || '—')}</td>
            <td class="item-row-actions">
                <button class="btn btn-sm btn-ghost" data-edit="${row.id}">Editar</button>
                <button class="btn btn-sm btn-ghost btn-write" data-del="${row.id}">Excluir</button>
            </td>`;
    },
});
tabela.columns = ['nome', 'cpf_cnpj', 'telefone', 'cidade', ''];

const form = document.getElementById('form-cliente');
const modal = document.getElementById('modal-cliente');
const cpfInput = form?.elements.namedItem('cpf_cnpj');
const cepInput = form?.elements.namedItem('cep');
if (cpfInput) Masks.bindCpfCnpj(cpfInput);
if (cepInput) Masks.bindCep(cepInput);

document.getElementById('btn-novo-cliente')?.addEventListener('click', () => {
    form.reset();
    document.getElementById('cliente-id').value = '';
    Masks.marcarInvalido(cpfInput, false);
    modal.showModal();
});

document.getElementById('tabela-clientes')?.addEventListener('click', async (e) => {
    const editId = e.target.dataset?.edit;
    const delId = e.target.dataset?.del;
    if (delId) {
        if (!await UI.confirm('Excluir este cliente?')) return;
        await API.delete(`/clientes/${delId}`);
        Toast.success('Cliente excluído');
        tabela.load();
        return;
    }
    if (!editId) return;
    const res = await API.get(`/clientes/${editId}`);
    const c = res.dados;
    document.getElementById('cliente-id').value = c.id;
    Object.keys(c).forEach((k) => {
        const inp = form.elements.namedItem(k);
        if (inp) inp.value = c[k] ?? '';
    });
    modal.showModal();
});

form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const cpf = cpfInput?.value || '';
    if (!Masks.validarCpfCnpj(cpf)) {
        Masks.marcarInvalido(cpfInput, true);
        Toast.error('CPF/CNPJ inválido');
        return;
    }
    Masks.marcarInvalido(cpfInput, false);
    const fd = new FormData(form);
    const body = Object.fromEntries(fd);
    const id = body.id;
    delete body.id;
    if (id) await API.put(`/clientes/${id}`, body);
    else await API.post('/clientes', body);
    Toast.success('Cliente salvo');
    modal.close();
    tabela.load();
});
