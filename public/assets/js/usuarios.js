const tabela = new DataTable('#tabela-usuarios', {
    defaultSort: 'nome',
    mapRow(row) {
        return `<td>${escapeHtml(row.nome)}</td>
            <td>${escapeHtml(row.email)}</td>
            <td><span class="badge badge-neutral">${escapeHtml(row.perfil)}</span></td>
            <td>${row.ativo ? '<span class="badge badge-success">Ativo</span>' : '<span class="badge badge-danger">Inativo</span>'}</td>
            <td class="item-row-actions">
                <button class="btn btn-sm btn-ghost" data-edit="${row.id}">Editar</button>
                <button class="btn btn-sm btn-ghost" data-del="${row.id}">Excluir</button>
            </td>`;
    },
});
tabela.columns = ['nome', 'email', 'perfil', 'ativo', ''];

const form = document.getElementById('form-usuario');
const modal = document.getElementById('modal-usuario');

document.getElementById('btn-novo-user')?.addEventListener('click', () => {
    form.reset();
    document.getElementById('user-id').value = '';
    document.getElementById('user-senha-req').style.display = '';
    modal.showModal();
});

document.getElementById('tabela-usuarios')?.addEventListener('click', async (e) => {
    if (e.target.dataset?.del) {
        if (!await UI.confirm('Excluir usuário?')) return;
        await API.delete(`/usuarios/${e.target.dataset.del}`);
        Toast.success('Excluído');
        tabela.load();
        return;
    }
    const id = e.target.dataset?.edit;
    if (!id) return;
    const rows = (await API.get('/usuarios?per_page=100')).dados.itens;
    const u = rows.find((r) => String(r.id) === id);
    if (!u) return;
    document.getElementById('user-id').value = u.id;
    form.elements.nome.value = u.nome;
    form.elements.email.value = u.email;
    form.elements.perfil.value = u.perfil;
    form.elements.ativo.checked = !!parseInt(u.ativo, 10);
    document.getElementById('user-senha-req').style.display = 'none';
    modal.showModal();
});

form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const body = Object.fromEntries(fd);
    body.ativo = form.elements.ativo.checked;
    const uid = body.id;
    delete body.id;
    if (uid) await API.put(`/usuarios/${uid}`, body);
    else await API.post('/usuarios', body);
    Toast.success('Usuário salvo');
    modal.close();
    tabela.load();
});
