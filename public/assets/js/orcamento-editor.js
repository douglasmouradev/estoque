(function () {
    const root = document.getElementById('orcamento-editor');
    if (!root) return;
    const id = root.dataset.id;
    let state = {};

    const id = root.dataset.id;
    let servicosCatalogo = null;

    async function servicos() {
        if (!servicosCatalogo) servicosCatalogo = (await API.get('/servicos/todos')).dados || [];
        return servicosCatalogo;
    }

    function bindServicoAutocomplete(inp) {
        if (!inp) return;
        UI.autocomplete(inp, async (q) => {
            const list = await servicos();
            return list.filter((s) => s.nome.toLowerCase().includes(q.toLowerCase()))
                .map((s) => ({ ...s, label: `${s.nome} — ${Format.moeda(s.preco_padrao)}` }));
        }, (s) => {
            inp.value = s.nome;
            const tr = inp.closest('tr');
            tr.querySelector('[data-f="preco_unitario"]').value = s.preco_padrao;
            atualizarTotais();
        });
    }

    async function load() {
        const res = await API.get(`/orcamentos/${id}`);
        state = res.dados;
        render();
    }

    function render() {
        const o = state;
        const editavel = !['convertido'].includes(o.status);
        const itens = o.itens || [];
        const totais = o.totais || Format.calcularTotaisOrcamento(itens, o.desconto_geral_percent, o.desconto_geral_valor);

        root.innerHTML = `
        ${Format.stepperOrcamento(o.status)}
        <div class="card toolbar">
            ${Format.statusOrcamento(o.status)}
            <button type="button" id="btn-pdf-preview" class="btn btn-ghost">Visualizar PDF</button>
            <a href="/orcamentos/${id}/pdf" class="btn btn-ghost" target="_blank" rel="noopener">Baixar PDF</a>
            ${editavel ? '<button type="button" id="btn-salvar" class="btn btn-primary btn-write">Salvar</button>' : ''}
            ${o.status === 'rascunho' || o.status === 'reprovado' ? '<button type="button" id="btn-enviar" class="btn btn-write">Enviar ao cliente</button>' : ''}
            ${o.status === 'enviado' ? `
                <button type="button" id="btn-aprovar" class="btn btn-primary btn-write">Aprovar</button>
                <button type="button" id="btn-reprovar" class="btn btn-write">Reprovar</button>` : ''}
            ${o.status === 'aprovado' ? '<button type="button" id="btn-os" class="btn btn-primary btn-write">Converter em OS</button>' : ''}
        </div>
        ${o.link_portal ? `
        <div class="card portal-link">
            <span class="muted">Link para o cliente:</span>
            <input type="text" class="input" id="link-portal" readonly value="${escapeHtml(o.link_portal)}">
            <button type="button" class="btn btn-sm" id="btn-copiar-link">Copiar link</button>
        </div>` : ''}
        <div class="card">
            <div class="orc-header-grid">
                <label>Cliente
                    <input type="text" id="busca-cliente" class="input" value="${escapeHtml(o.cliente_nome || '')}" ${editavel ? '' : 'readonly'}>
                    <input type="hidden" id="cliente_id" value="${o.cliente_id}">
                </label>
                <label>Buscar por placa
                    <input type="text" id="busca-placa" class="input" placeholder="ABC1D23" maxlength="8" ${editavel ? '' : 'readonly'}>
                </label>
                <label>Veículo
                    <select id="veiculo_id" class="input" ${editavel ? '' : 'disabled'}></select>
                </label>
                <label>Desc. geral %
                    <input type="number" step="0.01" id="desconto_geral_percent" value="${o.desconto_geral_percent || 0}" ${editavel ? '' : 'readonly'}>
                </label>
                <label>Desc. geral R$
                    <input type="number" step="0.01" id="desconto_geral_valor" value="${o.desconto_geral_valor || 0}" ${editavel ? '' : 'readonly'}>
                </label>
            </div>
            <div class="table-wrap">
                <table class="table" id="itens-table">
                    <thead><tr><th>Tipo</th><th>Descrição</th><th>Qtd</th><th>Preço un.</th><th>Estoque</th><th></th></tr></thead>
                    <tbody>${itens.map((it, i) => linhaItem(it, i, editavel)).join('')}</tbody>
                </table>
            </div>
            ${editavel ? `
            <div style="margin-top:0.75rem;display:flex;gap:0.5rem" class="btn-write">
                <button type="button" id="add-peca" class="btn">+ Peça</button>
                <button type="button" id="add-servico" class="btn">+ Serviço</button>
            </div>` : ''}
            <div class="orc-totais" id="orc-totais">
                <div><span>Subtotal</span><strong id="tot-sub">${Format.moeda(totais.subtotal)}</strong></div>
                <div><span>Desconto geral</span><strong id="tot-desc">${Format.moeda(totais.desconto_geral)}</strong></div>
                <div class="orc-total-final"><span>Total</span><strong id="tot-total">${Format.moeda(totais.total)}</strong></div>
            </div>
            <div id="alertas-estoque" class="alertas-estoque"></div>
        </div>
        ${(o.versoes || []).length ? `<div class="card"><h3>Versões anteriores</h3><ul class="muted">${o.versoes.map((v) => `<li>v${v.versao_anterior} — ${Format.dataHora(v.created_at)}</li>`).join('')}</ul></div>` : ''}`;

        carregarVeiculos(o.cliente_id, o.veiculo_id);
        atualizarTotais();
        atualizarAlertasEstoque();
        if (editavel) {
            bindClienteAutocomplete();
            bindPlacaBusca();
            document.getElementById('add-peca')?.addEventListener('click', () => addLinha('peca'));
            document.getElementById('add-servico')?.addEventListener('click', () => addLinha('servico'));
            document.getElementById('btn-salvar')?.addEventListener('click', salvar);
            document.getElementById('btn-enviar')?.addEventListener('click', enviar);
            document.getElementById('btn-aprovar')?.addEventListener('click', () => API.post(`/orcamentos/${id}/aprovar`, {}).then(() => { Toast.success('Aprovado'); load(); }));
            document.getElementById('btn-reprovar')?.addEventListener('click', reprovar);
            document.getElementById('btn-os')?.addEventListener('click', converterOs);
            root.querySelectorAll('[data-remove]').forEach((b) => b.addEventListener('click', () => { b.closest('tr')?.remove(); atualizarTotais(); atualizarAlertasEstoque(); }));
            root.querySelectorAll('.peca-busca').forEach(bindPecaAutocomplete);
            root.querySelectorAll('.servico-busca').forEach(bindServicoAutocomplete);
            bindRecalc();
        }
        document.getElementById('btn-pdf-preview')?.addEventListener('click', () => UI.pdfPreview(`/orcamentos/${id}/pdf`, `Orçamento #${o.numero || id}`));
        document.getElementById('btn-copiar-link')?.addEventListener('click', async () => {
            const inp = document.getElementById('link-portal');
            if (!inp?.value) return;
            try {
                await navigator.clipboard.writeText(inp.value);
                Toast.success('Link copiado');
            } catch (_) {
                inp.select();
                document.execCommand('copy');
                Toast.success('Link copiado');
            }
        });
    }

    function linhaItem(it, i, editavel) {
        const estoque = it.estoque_atual != null ? it.estoque_atual : '';
        const alerta = it.tipo === 'peca' && it.peca_id && parseFloat(it.quantidade) > parseFloat(estoque || 0);
        return `<tr data-i="${i}" data-estoque="${estoque}" data-tipo="${it.tipo}">
            <td>${it.tipo}</td>
            <td>${it.tipo === 'peca' ? `<input class="input peca-busca" data-f="descricao" value="${escapeHtml(it.descricao)}" ${editavel ? '' : 'readonly'}>` : `<input class="input servico-busca" data-f="descricao" value="${escapeHtml(it.descricao)}" ${editavel ? '' : 'readonly'}>`}
                <input type="hidden" data-f="tipo" value="${it.tipo}">
                <input type="hidden" data-f="peca_id" value="${it.peca_id || ''}"></td>
            <td><input type="number" step="0.001" class="input input-qtd" data-f="quantidade" value="${it.quantidade}" style="width:80px" ${editavel ? '' : 'readonly'}></td>
            <td><input type="number" step="0.01" class="input input-preco" data-f="preco_unitario" value="${it.preco_unitario}" style="width:100px" ${editavel ? '' : 'readonly'}></td>
            <td class="col-estoque ${alerta ? 'text-danger' : ''}">${it.tipo === 'peca' ? (estoque !== '' ? estoque : '—') : '—'}</td>
            <td>${editavel ? '<button type="button" class="btn btn-sm btn-ghost" data-remove>×</button>' : ''}</td>
        </tr>`;
    }

    function addLinha(tipo) {
        const tbody = document.querySelector('#itens-table tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = linhaItem({ tipo, descricao: '', quantidade: 1, preco_unitario: 0, peca_id: '' }, 0, true).replace(/<tr[^>]*>|<\/tr>/g, '');
        tr.dataset.tipo = tipo;
        tbody.appendChild(tr);
        tr.querySelector('[data-remove]')?.addEventListener('click', () => { tr.remove(); atualizarTotais(); atualizarAlertasEstoque(); });
        if (tipo === 'peca') bindPecaAutocomplete(tr.querySelector('.peca-busca'));
        else bindServicoAutocomplete(tr.querySelector('.servico-busca'));
        bindRecalcRow(tr);
    }

    function bindRecalc() {
        ['desconto_geral_percent', 'desconto_geral_valor'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', atualizarTotais);
        });
        document.querySelectorAll('#itens-table tbody tr').forEach(bindRecalcRow);
    }

    function bindRecalcRow(tr) {
        tr.querySelectorAll('.input-qtd, .input-preco').forEach((inp) => {
            inp.addEventListener('input', () => { atualizarTotais(); atualizarAlertasEstoque(); atualizarColEstoque(tr); });
        });
    }

    function atualizarColEstoque(tr) {
        const col = tr.querySelector('.col-estoque');
        if (!col || tr.dataset.tipo !== 'peca') return;
        const est = parseFloat(tr.dataset.estoque);
        const qtd = parseFloat(tr.querySelector('[data-f="quantidade"]')?.value) || 0;
        col.classList.toggle('text-danger', !Number.isNaN(est) && qtd > est);
    }

    function atualizarTotais() {
        const tot = Format.calcularTotaisOrcamento(
            coletarItens(),
            document.getElementById('desconto_geral_percent')?.value,
            document.getElementById('desconto_geral_valor')?.value
        );
        const sub = document.getElementById('tot-sub');
        const desc = document.getElementById('tot-desc');
        const total = document.getElementById('tot-total');
        if (sub) sub.textContent = Format.moeda(tot.subtotal);
        if (desc) desc.textContent = Format.moeda(tot.desconto_geral);
        if (total) total.textContent = Format.moeda(tot.total);
    }

    function atualizarAlertasEstoque() {
        const box = document.getElementById('alertas-estoque');
        if (!box) return;
        const msgs = [];
        document.querySelectorAll('#itens-table tbody tr').forEach((tr) => {
            if (tr.dataset.tipo !== 'peca' && tr.querySelector('[data-f="tipo"]')?.value !== 'peca') return;
            const pecaId = tr.querySelector('[data-f="peca_id"]')?.value;
            if (!pecaId) return;
            const est = parseFloat(tr.dataset.estoque);
            const qtd = parseFloat(tr.querySelector('[data-f="quantidade"]')?.value) || 0;
            const desc = tr.querySelector('[data-f="descricao"]')?.value || 'Peça';
            if (!Number.isNaN(est) && qtd > est) {
                msgs.push(`${desc}: solicitado ${qtd}, saldo ${est}`);
            }
        });
        if (!msgs.length) { box.innerHTML = ''; return; }
        box.innerHTML = `<div class="alert alert-error"><strong>Estoque insuficiente</strong><ul>${msgs.map((m) => `<li>${escapeHtml(m)}</li>`).join('')}</ul></div>`;
    }

    function bindClienteAutocomplete() {
        const inp = document.getElementById('busca-cliente');
        if (!inp) return;
        UI.autocomplete(inp, async (q) => {
            const r = await API.get(`/clientes/buscar?q=${encodeURIComponent(q)}`);
            return (r.dados || []).map((c) => ({ ...c, label: `${c.nome} (${c.cpf_cnpj})` }));
        }, (c) => {
            inp.value = c.nome;
            document.getElementById('cliente_id').value = c.id;
            carregarVeiculos(c.id);
        });
    }

    function bindPlacaBusca() {
        const inp = document.getElementById('busca-placa');
        if (!inp || typeof mascaraPlaca !== 'function') return;
        inp.addEventListener('input', () => { inp.value = mascaraPlaca(inp.value); });
        let timer;
        inp.addEventListener('input', () => {
            clearTimeout(timer);
            const placa = inp.value.replace(/[^A-Za-z0-9]/g, '');
            if (placa.length < 7) return;
            timer = setTimeout(async () => {
                const r = await API.get(`/veiculos/placa?placa=${encodeURIComponent(placa)}`);
                const v = r.dados;
                if (!v) { Toast.info('Veículo não encontrado'); return; }
                document.getElementById('cliente_id').value = v.cliente_id;
                document.getElementById('busca-cliente').value = v.cliente_nome || '';
                await carregarVeiculos(v.cliente_id, v.id);
                Toast.success('Veículo localizado');
            }, 400);
        });
    }

    async function carregarVeiculos(clienteId, selected) {
        const sel = document.getElementById('veiculo_id');
        if (!sel || !clienteId) { if (sel) sel.innerHTML = '<option value="">Selecione o cliente</option>'; return; }
        const r = await API.get(`/veiculos/cliente/${clienteId}?per_page=100`);
        const itens = r.dados?.itens || [];
        sel.innerHTML = itens.map((v) => `<option value="${v.id}" ${String(v.id) === String(selected) ? 'selected' : ''}>${escapeHtml(v.placa)} — ${escapeHtml(v.marca)} ${escapeHtml(v.modelo)}</option>`).join('');
    }

    function bindPecaAutocomplete(inp) {
        if (!inp) return;
        UI.autocomplete(inp, async (q) => {
            const r = await API.get(`/estoque/autocomplete?q=${encodeURIComponent(q)}`);
            return (r.dados || []).map((p) => ({
                ...p,
                label: `${p.codigo_interno} — ${p.descricao} (saldo: ${p.estoque_atual})`,
            }));
        }, (p) => {
            inp.value = p.descricao;
            const tr = inp.closest('tr');
            tr.dataset.estoque = p.estoque_atual;
            tr.dataset.tipo = 'peca';
            tr.querySelector('[data-f="peca_id"]').value = p.id;
            tr.querySelector('[data-f="preco_unitario"]').value = p.preco_venda;
            const col = tr.querySelector('.col-estoque');
            if (col) col.textContent = p.estoque_atual;
            atualizarTotais();
            atualizarAlertasEstoque();
            atualizarColEstoque(tr);
        });
    }

    function coletarItens() {
        const itens = [];
        document.querySelectorAll('#itens-table tbody tr').forEach((tr) => {
            const item = {};
            tr.querySelectorAll('[data-f]').forEach((inp) => { item[inp.dataset.f] = inp.value; });
            if (item.descricao) itens.push(item);
        });
        return itens;
    }

    function payload() {
        return {
            cliente_id: parseInt(document.getElementById('cliente_id').value, 10),
            veiculo_id: parseInt(document.getElementById('veiculo_id').value, 10),
            desconto_geral_percent: document.getElementById('desconto_geral_percent').value,
            desconto_geral_valor: document.getElementById('desconto_geral_valor').value,
            itens: coletarItens(),
        };
    }

    async function salvar() {
        await API.put(`/orcamentos/${id}`, payload());
        Toast.success('Orçamento salvo');
        load();
    }

    async function enviar() {
        if (document.getElementById('alertas-estoque')?.textContent.trim()) {
            if (!await UI.confirm('Há itens com estoque insuficiente. Deseja enviar mesmo assim?')) return;
        }
        await API.put(`/orcamentos/${id}`, payload());
        const res = await API.post(`/orcamentos/${id}/enviar`, {});
        if (res.dados?.link_portal) {
            try {
                await navigator.clipboard.writeText(res.dados.link_portal);
                Toast.success('Enviado! Link copiado para a área de transferência.');
            } catch (_) {
                Toast.success('Enviado ao cliente');
            }
        } else {
            Toast.success('Enviado ao cliente');
        }
        load();
    }

    async function reprovar() {
        const obs = await UI.prompt('Motivo da reprovação (opcional):', 'Reprovar orçamento');
        if (obs === null) return;
        await API.post(`/orcamentos/${id}/reprovar`, { observacao_cliente: obs });
        Toast.success('Orçamento reprovado');
        load();
    }

    async function converterOs() {
        if (!await UI.confirm('Converter este orçamento em Ordem de Serviço?')) return;
        const r = await API.post(`/orcamentos/${id}/converter-os`, {});
        location.href = `/os/${r.dados.ordem_servico_id}`;
    }

    load();
})();
