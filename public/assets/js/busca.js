(function () {
    const inp = document.getElementById('busca-global');
    const box = document.getElementById('busca-resultados');
    if (!inp || !box) return;
    let timer;
    inp.addEventListener('input', () => {
        clearTimeout(timer);
        const q = inp.value.trim();
        if (q.length < 2) { box.innerHTML = ''; box.classList.add('hidden'); return; }
        timer = setTimeout(async () => {
            const r = await API.get(`/busca?q=${encodeURIComponent(q)}`);
            const d = r.dados;
            let html = '';
            (d.clientes || []).forEach((c) => { html += `<a href="/clientes/${c.id}" class="busca-item">Cliente: ${escapeHtml(c.nome)}</a>`; });
            (d.veiculos || []).forEach((v) => { html += `<a href="/clientes/${v.cliente_id || ''}" class="busca-item">Placa: ${escapeHtml(v.placa)} — ${escapeHtml(v.marca)}</a>`; });
            (d.pecas || []).forEach((p) => { html += `<a href="/estoque/${p.id}" class="busca-item">Peça: ${escapeHtml(p.codigo_interno)}</a>`; });
            (d.ordens_servico || []).forEach((o) => { html += `<a href="/os/${o.id}" class="busca-item">OS #${o.numero} — ${escapeHtml(o.cliente_nome)}</a>`; });
            box.innerHTML = html || '<p class="muted">Nenhum resultado</p>';
            box.classList.remove('hidden');
        }, 300);
    });
    document.addEventListener('click', (e) => {
        if (!box.contains(e.target) && e.target !== inp) box.classList.add('hidden');
    });
})();
