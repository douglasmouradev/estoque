let categorias = [];

async function loadCategorias() {
    const r = await API.get('/categorias');
    categorias = r.dados || [];
    const sel = document.querySelector('#form-peca select[name="categoria_id"]');
    if (sel) {
        sel.innerHTML = '<option value="">—</option>' + categorias.map((c) => `<option value="${c.id}">${escapeHtml(c.nome)}</option>`).join('');
    }
}
loadCategorias();

const tabela = new DataTable('#tabela-estoque', {
    defaultSort: 'descricao',
    emptyMessage: 'Nenhuma peça cadastrada',
    emptyCta: {
        label: '+ Cadastrar primeira peça',
        onClick: () => document.getElementById('btn-nova-peca')?.click(),
    },
    mapRow(row) {
        const alerta = parseFloat(row.estoque_atual) <= parseFloat(row.estoque_minimo);
        return `<td><a href="/estoque/${row.id}">${escapeHtml(row.codigo_interno)}</a></td>
            <td>${escapeHtml(row.descricao)}</td>
            <td class="${alerta ? 'text-danger' : ''}">${row.estoque_atual}</td>
            <td>${row.estoque_minimo}</td>
            <td>${Format.moeda(row.preco_venda)}</td>
            <td>${escapeHtml(row.localizacao || '—')}</td>
            <td><button class="btn btn-sm btn-ghost btn-write" data-edit="${row.id}">Editar</button></td>`;
    },
});
tabela.columns = ['codigo_interno', 'descricao', 'estoque_atual', 'estoque_minimo', 'preco_venda', 'localizacao', ''];

document.getElementById('filtro-minimo')?.addEventListener('change', (e) => {
    tabela.state.abaixo_minimo = e.target.checked ? '1' : '';
    tabela.load();
});

document.getElementById('btn-paradas')?.addEventListener('click', () => { location.href = '/estoque/paradas'; });

document.getElementById('btn-importar')?.addEventListener('click', () => document.getElementById('csv-file').click());
document.getElementById('csv-file')?.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('arquivo', file);
    try {
        const res = await fetch('/estoque/importar', {
            method: 'POST',
            body: fd,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': API.csrf() },
        });
        const data = await res.json();
        if (data.sucesso) {
            const d = data.dados;
            Toast.success(`Importados: ${d.importados}`);
            if (d.erros?.length) {
                await UI.relatorio(
                    `Importação concluída — ${d.erros.length} erro(s)`,
                    d.erros.map((er) => `Linha ${er.linha}: ${er.mensagem}`)
                );
            }
            tabela.load();
        } else {
            Toast.error(data.erro);
        }
    } catch (err) {
        Toast.error('Falha na importação');
    } finally {
        e.target.value = '';
    }
});

const modal = document.getElementById('modal-peca');
const form = document.getElementById('form-peca');

document.getElementById('btn-nova-peca')?.addEventListener('click', () => {
    form.reset();
    document.getElementById('peca-id').value = '';
    document.getElementById('campo-estoque-inicial')?.classList.remove('hidden');
    modal.showModal();
});

document.getElementById('tabela-estoque')?.addEventListener('click', async (e) => {
    const id = e.target.dataset?.edit;
    if (!id) return;
    const res = await API.get(`/estoque/${id}`);
    const p = res.dados;
    document.getElementById('peca-id').value = p.id;
    ['codigo_interno', 'codigo_oem', 'descricao', 'unidade', 'marca', 'localizacao', 'estoque_minimo', 'preco_venda'].forEach((f) => {
        const el = form.elements.namedItem(f);
        if (el) el.value = p[f] ?? '';
    });
    if (form.elements.namedItem('categoria_id')) form.elements.namedItem('categoria_id').value = p.categoria_id || '';
    document.getElementById('campo-estoque-inicial')?.classList.add('hidden');
    modal.showModal();
});

form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const body = Object.fromEntries(fd);
    const pid = body.id;
    delete body.id;
    if (pid) {
        delete body.estoque_inicial;
        await API.put(`/estoque/${pid}`, body);
    } else {
        await API.post('/estoque', body);
    }
    Toast.success('Peça salva');
    modal.close();
    tabela.load();
});
