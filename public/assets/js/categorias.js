(async function () {
    const lista = document.getElementById('lista-categorias');
    if (!lista) return;

    const modal = document.getElementById('modal-categoria');
    const form = document.getElementById('form-categoria');

    async function carregar() {
        lista.classList.add('is-loading');
        try {
            const r = await API.get('/categorias', { local: lista });
            const cats = r.dados || [];
            if (!cats.length) {
                lista.innerHTML = `<div class="empty-state-inner">
                    <p>Nenhuma categoria cadastrada</p>
                    <button type="button" class="btn btn-primary btn-write" id="cta-categoria">+ Criar categoria</button>
                </div>`;
                document.getElementById('cta-categoria')?.addEventListener('click', abrirNovo);
                return;
            }
            lista.innerHTML = `<table class="table"><thead><tr><th>Nome</th><th></th></tr></thead><tbody>
                ${cats.map((c) => `<tr>
                    <td>${escapeHtml(c.nome)}</td>
                    <td class="item-row-actions">
                        <button type="button" class="btn btn-sm btn-ghost btn-write" data-edit="${c.id}">Editar</button>
                        <button type="button" class="btn btn-sm btn-ghost btn-write" data-del="${c.id}">Excluir</button>
                    </td>
                </tr>`).join('')}
            </tbody></table>`;
            lista.querySelectorAll('[data-edit]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    document.getElementById('categoria-edit-id').value = btn.dataset.edit;
                    document.getElementById('categoria-nome').value = btn.closest('tr')?.querySelector('td')?.textContent?.trim() || '';
                    modal.showModal();
                });
            });
            lista.querySelectorAll('[data-del]').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    if (!await UI.confirm('Excluir esta categoria? Peças vinculadas impedem a exclusão.')) return;
                    await API.delete(`/categorias/${btn.dataset.del}`);
                    Toast.success('Categoria excluída');
                    carregar();
                });
            });
        } finally {
            lista.classList.remove('is-loading');
        }
    }

    function abrirNovo() {
        form.reset();
        document.getElementById('categoria-edit-id').value = '';
        modal.showModal();
    }

    document.getElementById('btn-nova-categoria')?.addEventListener('click', abrirNovo);

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const nome = document.getElementById('categoria-nome').value.trim();
        const id = document.getElementById('categoria-edit-id').value;
        if (id) await API.put(`/categorias/${id}`, { nome });
        else await API.post('/categorias', { nome });
        Toast.success('Categoria salva');
        modal.close();
        carregar();
    });

    carregar();
})();
