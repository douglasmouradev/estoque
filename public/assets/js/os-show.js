(async function () {
    const root = document.getElementById('os-detalhe');
    const id = root?.dataset.id;
    if (!id) return;

    async function load() {
        const res = await API.get(`/os/${id}`);
        const o = res.dados;
        const podeFinalizar = o.pode_finalizar;
        const podeEditarItens = o.pode_editar_itens;
        const editavel = !['finalizada', 'cancelada'].includes(o.status);

        const podeFin = o.pode_finalizar;
        const totalOs = parseFloat(o.valor_total) || 0;
        const pagoOs = parseFloat(o.valor_pago) || 0;
        const saldoOs = Math.max(0, Math.round((totalOs - pagoOs) * 100) / 100);

        root.innerHTML = `
        ${Format.stepperOs(o.status)}
        <div class="card toolbar">
            <h2 style="margin:0">OS #${o.numero}</h2>
            ${Format.statusOs(o.status)}
            <select id="status-select" class="input" style="width:auto" ${editavel ? '' : 'disabled'}>
                ${['aberta', 'em_andamento', 'aguardando_peca', 'finalizada', 'cancelada'].map((s) =>
                    `<option value="${s}" ${s === o.status ? 'selected' : ''}>${s.replace(/_/g, ' ')}</option>`).join('')}
            </select>
            ${podeFinalizar && o.status !== 'finalizada' ? '<button id="btn-finalizar" class="btn btn-primary btn-write">Finalizar (baixa estoque)</button>' : ''}
            <button type="button" id="btn-pdf-preview" class="btn btn-ghost">Visualizar PDF</button>
            <a href="/os/${id}/pdf" class="btn btn-ghost" target="_blank" rel="noopener">Baixar PDF</a>
        </div>
        ${o.link_portal ? `
        <div class="card portal-link">
            <span class="muted">Link para o cliente acompanhar a OS:</span>
            <input type="text" class="input" id="link-os-portal" readonly value="${escapeHtml(o.link_portal)}">
            <button type="button" class="btn btn-sm" id="btn-copiar-os-link">Copiar</button>
        </div>` : ''}
        <p class="muted">${escapeHtml(o.cliente_nome)} · ${escapeHtml(o.placa)} · ${escapeHtml(o.marca)} ${escapeHtml(o.modelo)}</p>
        <div class="card">
            <h3>Itens <small class="muted">(marque concluído para baixa na finalização)</small></h3>
            ${(o.itens || []).length ? `<ul class="os-itens-list">${(o.itens || []).map((it) => `
                <li class="os-item-row">
                    <label>
                        <input type="checkbox" data-item="${it.id}" ${it.concluido ? 'checked' : ''} ${editavel ? '' : 'disabled'}>
                        <span>${escapeHtml(it.descricao)} x${it.quantidade} ${it.tipo === 'peca' ? '[peça]' : '[serviço]'}</span>
                    </label>
                    ${podeEditarItens ? `<button type="button" class="btn btn-sm btn-ghost btn-write" data-del-item="${it.id}">Remover</button>` : ''}
                </li>`).join('')}</ul>` : '<p class="muted empty-state-inner">Nenhum item na OS.</p>'}
            ${podeEditarItens ? `
            <div class="os-add-item btn-write" style="margin-top:1rem">
                <div class="toolbar" style="flex-wrap:wrap;gap:0.5rem">
                    <select id="item-tipo" class="input" style="width:auto">
                        <option value="peca">Peça</option>
                        <option value="servico">Serviço</option>
                    </select>
                    <input type="text" id="item-desc" class="input peca-busca" placeholder="Descrição ou busque peça..." style="min-width:200px;flex:1">
                    <input type="hidden" id="item-peca-id">
                    <input type="number" step="0.001" id="item-qtd" class="input" value="1" style="width:80px" min="0.001">
                    <input type="number" step="0.01" id="item-preco" class="input" value="0" style="width:100px" min="0">
                    <button type="button" id="btn-add-item" class="btn btn-primary">Adicionar</button>
                </div>
            </div>` : ''}
        </div>
        ${o.status === 'finalizada' ? `
        <div class="card">
            <h3>Financeiro</h3>
            <p class="muted">Total: <strong>${Format.moeda(totalOs)}</strong> · Pago: <strong>${Format.moeda(pagoOs)}</strong> · Status: <strong>${escapeHtml(o.status_pagamento || 'pendente')}</strong></p>
            ${podeFin && saldoOs > 0 ? `
            <form id="form-pagamento" class="toolbar btn-write" style="margin-top:0.75rem;flex-wrap:wrap">
                <input type="number" step="0.01" name="valor" class="input" min="0.01" max="${saldoOs}" value="${saldoOs}" style="width:120px">
                <select name="forma_pagamento" class="input" style="width:auto">
                    <option value="pix">PIX</option>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="cartao_credito">Cartão crédito</option>
                    <option value="cartao_debito">Cartão débito</option>
                    <option value="transferencia">Transferência</option>
                    <option value="outro">Outro</option>
                </select>
                <button type="submit" class="btn btn-primary">Registrar pagamento</button>
            </form>` : (saldoOs <= 0 ? '<p class="muted" style="color:var(--success)">Quitada</p>' : '')}
            ${(o.pagamentos || []).length ? `<ul style="margin-top:0.75rem;font-size:0.85rem">${(o.pagamentos || []).map((p) =>
                `<li>${Format.dataHora(p.created_at)} — ${Format.moeda(p.valor)} (${escapeHtml(p.forma_pagamento)})</li>`).join('')}</ul>` : ''}
        </div>` : ''}
        <div class="card"><h3>Checklist</h3>
        <form id="form-check" class="toolbar btn-write"><input name="descricao" class="input" placeholder="Novo item" required><button class="btn">+</button></form>
        <ul>${(o.checklist || []).map((c) => `<li><label><input type="checkbox" data-chk="${c.id}" ${c.concluido ? 'checked' : ''}> ${escapeHtml(c.descricao)}</label></li>`).join('')}</ul></div>
        <div class="card"><h3>Horas trabalhadas</h3>
        <form id="form-horas" class="form-grid btn-write">
            <select name="mecanico_id">${(o.mecanicos || []).map((m) => `<option value="${m.id}">${escapeHtml(m.nome)}</option>`)}</select>
            <input type="date" name="data_trabalho" required value="${new Date().toISOString().slice(0, 10)}">
            <input type="number" step="0.25" name="horas" placeholder="Horas" required>
            <input name="descricao" placeholder="Descrição">
            <button class="btn btn-primary">Registrar</button>
        </form>
        <ul style="margin-top:1rem">${(o.horas || []).map((h) => `<li>${Format.data(h.data_trabalho)} — ${escapeHtml(h.mecanico_nome)}: ${h.horas}h</li>`).join('')}</ul></div>`;

        document.getElementById('status-select')?.addEventListener('change', async (e) => {
            if (e.target.value === 'cancelada' && !await UI.confirm('Cancelar esta OS? Se já finalizada, o estoque das peças será estornado.')) {
                e.target.value = o.status;
                return;
            }
            try {
                await API.post(`/os/${id}/status`, { status: e.target.value });
                Toast.success('Status atualizado');
                load();
            } catch (_) {
                e.target.value = o.status;
            }
        });
        document.getElementById('btn-finalizar')?.addEventListener('click', async () => {
            if (!await UI.confirm('Finalizar OS e baixar estoque dos itens concluídos (peças)?')) return;
            await API.post(`/os/${id}/finalizar`, {});
            Toast.success('OS finalizada');
            load();
        });
        document.getElementById('btn-pdf-preview')?.addEventListener('click', () => UI.pdfPreview(`/os/${id}/pdf`, `OS #${o.numero}`));
        document.getElementById('btn-copiar-os-link')?.addEventListener('click', async () => {
            const inp = document.getElementById('link-os-portal');
            if (!inp?.value) return;
            try { await navigator.clipboard.writeText(inp.value); Toast.success('Link copiado'); }
            catch (_) { inp.select(); document.execCommand('copy'); Toast.success('Link copiado'); }
        });
        root.querySelectorAll('[data-item]').forEach((cb) => {
            cb.addEventListener('change', () => API.post(`/os/${id}/itens/${cb.dataset.item}/toggle`, { concluido: cb.checked }));
        });
        root.querySelectorAll('[data-del-item]').forEach((btn) => {
            btn.addEventListener('click', async () => {
                if (!await UI.confirm('Remover este item?')) return;
                await API.delete(`/os/${id}/itens/${btn.dataset.delItem}`);
                Toast.success('Item removido');
                load();
            });
        });
        root.querySelectorAll('[data-chk]').forEach((cb) => {
            cb.addEventListener('change', () => API.post(`/os/checklist/${cb.dataset.chk}/toggle`, { concluido: cb.checked }));
        });
        document.getElementById('form-check')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await API.post(`/os/${id}/checklist`, Object.fromEntries(new FormData(e.target)));
            load();
        });
        document.getElementById('form-horas')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await API.post(`/os/${id}/horas`, Object.fromEntries(new FormData(e.target)));
            load();
        });
        document.getElementById('form-pagamento')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            await API.post(`/os/${id}/pagamento`, Object.fromEntries(new FormData(e.target)));
            Toast.success('Pagamento registrado');
            load();
        });

        if (podeEditarItens) {
            const descInp = document.getElementById('item-desc');
            const tipoSel = document.getElementById('item-tipo');
            let servicosCache = null;
            const bindPeca = () => {
                descInp.classList.add('peca-busca');
                descInp.classList.remove('servico-busca');
                UI.autocomplete(descInp, async (q) => {
                    const r = await API.get(`/estoque/autocomplete?q=${encodeURIComponent(q)}`);
                    return (r.dados || []).map((p) => ({
                        ...p,
                        label: `${p.codigo_interno} — ${p.descricao}`,
                    }));
                }, (p) => {
                    descInp.value = p.descricao;
                    document.getElementById('item-peca-id').value = p.id;
                    document.getElementById('item-preco').value = p.preco_venda;
                });
            };
            const bindServico = () => {
                descInp.classList.remove('peca-busca');
                descInp.classList.add('servico-busca');
                document.getElementById('item-peca-id').value = '';
                UI.autocomplete(descInp, async (q) => {
                    if (!servicosCache) servicosCache = (await API.get('/servicos/todos')).dados || [];
                    return servicosCache
                        .filter((s) => s.nome.toLowerCase().includes(q.toLowerCase()))
                        .map((s) => ({ ...s, label: `${s.nome} — ${Format.moeda(s.preco_padrao)}` }));
                }, (s) => {
                    descInp.value = s.nome;
                    document.getElementById('item-preco').value = s.preco_padrao;
                });
            };
            bindPeca();
            tipoSel?.addEventListener('change', () => {
                document.getElementById('item-peca-id').value = '';
                if (tipoSel.value === 'peca') bindPeca();
                else bindServico();
            });
            document.getElementById('btn-add-item')?.addEventListener('click', async () => {
                const tipo = tipoSel.value;
                const descricao = descInp.value.trim();
                if (!descricao) { Toast.error('Informe a descrição'); return; }
                await API.post(`/os/${id}/itens`, {
                    tipo,
                    descricao,
                    quantidade: document.getElementById('item-qtd').value,
                    preco_unitario: document.getElementById('item-preco').value,
                    peca_id: tipo === 'peca' ? document.getElementById('item-peca-id').value || null : null,
                });
                Toast.success('Item adicionado');
                load();
            });
        }
    }
    load();
})();
