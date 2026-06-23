(async function () {
    const root = document.getElementById('cliente-detalhe');
    const clienteId = root?.dataset.id;
    if (!clienteId) return;

    async function load() {
        const res = await API.get(`/clientes/${clienteId}`);
        const c = res.dados;
        root.innerHTML = `<div class="card">
            <h2>${escapeHtml(c.nome)}</h2>
            <p>${escapeHtml(c.cpf_cnpj)} · ${escapeHtml(c.telefone || '—')} · ${escapeHtml(c.email || '—')}</p>
            <p class="muted">${escapeHtml(c.logradouro || '')} ${escapeHtml(c.numero || '')} — ${escapeHtml(c.bairro || '')} — ${escapeHtml(c.cidade || '')} ${escapeHtml(c.uf || '')}</p>
        </div>`;
        renderVeiculos(c.veiculos?.itens || []);
    }

    function renderVeiculos(itens) {
        const el = document.getElementById('tabela-veiculos');
        const pode = !document.body.classList.contains('somente-leitura');
        let html = '<div class="table-wrap"><table class="table"><thead><tr><th>Placa</th><th>Marca</th><th>Modelo</th><th>KM</th><th></th></tr></thead><tbody>';
        itens.forEach((v) => {
            html += `<tr><td>${escapeHtml(v.placa)}</td><td>${escapeHtml(v.marca)}</td>
                <td>${escapeHtml(v.modelo)}</td><td>${v.km_atual}</td>
                <td>${pode ? `<button class="btn btn-sm btn-ghost" data-edit-v="${v.id}">Editar</button>
                <button class="btn btn-sm btn-ghost" data-del-v="${v.id}">Excluir</button>` : ''}</td></tr>`;
        });
        html += '</tbody></table></div>';
        el.innerHTML = html;
        el.querySelectorAll('[data-edit-v]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const vid = btn.dataset.editV;
                const v = itens.find((x) => String(x.id) === vid);
                if (!v) return;
                document.getElementById('veiculo-id').value = v.id;
                ['placa','marca','modelo','ano','km_atual'].forEach((f) => {
                    const inp = document.getElementById('form-veiculo').elements.namedItem(f);
                    if (inp) inp.value = v[f] ?? '';
                });
                document.getElementById('modal-veiculo').showModal();
            });
        });
        el.querySelectorAll('[data-del-v]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!await UI.confirm('Excluir veículo?')) return;
                await API.delete(`/veiculos/${btn.dataset.delV}`);
                Toast.success('Veículo excluído');
                load();
            });
        });
    }

    document.getElementById('btn-novo-veiculo')?.addEventListener('click', () => {
        document.getElementById('form-veiculo').reset();
        document.getElementById('veiculo-id').value = '';
        document.getElementById('modal-veiculo').showModal();
    });

    bindPlacaInput(document.getElementById('placa'));

    document.getElementById('form-veiculo')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const body = Object.fromEntries(fd);
        body.cliente_id = clienteId;
        const vid = body.id;
        delete body.id;
        if (vid) await API.put(`/veiculos/${vid}`, body);
        else await API.post('/veiculos', body);
        Toast.success('Veículo salvo');
        document.getElementById('modal-veiculo').close();
        load();
    });

    load();
})();
