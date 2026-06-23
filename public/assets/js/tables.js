class DataTable {
    constructor(container, options = {}) {
        this.el = typeof container === 'string' ? document.querySelector(container) : container;
        if (!this.el) return;
        this.endpoint = options.endpoint || this.el.dataset.endpoint;
        this.columns = (options.columns || this.el.dataset.columns || '').split(',').filter(Boolean);
        this.mapRow = options.mapRow || null;
        this.emptyCta = options.emptyCta || null;
        this.emptyMessage = options.emptyMessage || 'Nenhum registro encontrado';
        this.onLoaded = options.onLoaded || null;
        this.state = { page: 1, per_page: 20, sort: options.defaultSort || 'id', dir: 'DESC', q: '' };
        this.bind();
        this.render();
    }

    bind() {
        const search = document.querySelector(optionsSearchId(this.el));
        if (search) {
            let t;
            search.addEventListener('input', () => {
                clearTimeout(t);
                t = setTimeout(() => { this.state.q = search.value; this.state.page = 1; this.load(); }, 300);
            });
        }
    }

    skeleton() {
        let rows = '';
        for (let i = 0; i < 5; i++) {
            rows += '<tr class="skeleton-row"><td colspan="6"><div class="skeleton-bar"></div></td></tr>';
        }
        return `<div class="table-wrap table-loading"><table class="table"><tbody>${rows}</tbody></table></div>`;
    }

    async load() {
        this.el.classList.add('is-loading');
        const params = new URLSearchParams(this.state);
        try {
            const res = await API.get(`${this.endpoint}?${params}`, { local: this.el });
            const d = res.dados;
            this.el.innerHTML =
                '<div class="table-wrap">' + this.buildTable(d.itens) + '</div>' +
                this.buildPagination(d);
            this.bindTableEvents();
            if (this.onLoaded) this.onLoaded(d);
        } catch (_) {
            this.el.innerHTML = '<div class="empty-state"><p>Erro ao carregar dados. Tente novamente.</p></div>';
        } finally {
            this.el.classList.remove('is-loading');
        }
    }

    bindTableEvents() {
        this.el.querySelectorAll('th[data-sort]').forEach((th) => {
            th.addEventListener('click', () => {
                const col = th.dataset.sort;
                if (this.state.sort === col) this.state.dir = this.state.dir === 'ASC' ? 'DESC' : 'ASC';
                else { this.state.sort = col; this.state.dir = 'ASC'; }
                this.load();
            });
        });
        this.el.querySelectorAll('[data-page]').forEach((btn) => {
            btn.addEventListener('click', () => {
                this.state.page = parseInt(btn.dataset.page, 10);
                this.load();
            });
        });
        this.el.querySelector('[data-per-page]')?.addEventListener('change', (e) => {
            this.state.per_page = parseInt(e.target.value, 10);
            this.state.page = 1;
            this.load();
        });
        this.el.querySelector('[data-empty-cta]')?.addEventListener('click', () => {
            if (this.emptyCta?.onClick) this.emptyCta.onClick();
        });
    }

    buildTable(itens) {
        const cols = this.columns.length ? this.columns : (itens[0] ? Object.keys(itens[0]) : []);
        let html = '<table class="table"><thead><tr>';
        cols.forEach((c) => {
            if (c === '') return;
            const sorted = this.state.sort === c;
            const icon = sorted ? (this.state.dir === 'ASC' ? '▲' : '▼') : '⇅';
            html += `<th data-sort="${c}" class="${sorted ? 'th-sorted' : ''}">${formatColumnLabel(c)}<span class="sort-icon" aria-hidden="true">${icon}</span></th>`;
        });
        if (this.mapRow && !cols.includes('')) html += '<th></th>';
        html += '</tr></thead><tbody>';
        itens.forEach((row) => {
            html += '<tr>';
            if (this.mapRow) html += this.mapRow(row);
            else cols.forEach((c) => {
                if (c === '') return;
                html += `<td>${escapeHtml(String(row[c] ?? ''))}</td>`;
            });
            html += '</tr>';
        });
        if (!itens.length) {
            const colSpan = cols.filter((c) => c !== '').length + (this.mapRow ? 1 : 0);
            const cta = this.emptyCta
                ? `<button type="button" class="btn btn-primary btn-write" data-empty-cta>${escapeHtml(this.emptyCta.label)}</button>`
                : '';
            html += `<tr><td colspan="${colSpan || 1}" class="empty-state">
                <div class="empty-state-inner">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <p>${escapeHtml(this.emptyMessage)}</p>${cta}
                </div></td></tr>`;
        }
        html += '</tbody></table>';
        return html;
    }

    buildPagination(d) {
        let html = '<div class="pagination">';
        html += `<button type="button" data-page="${d.page - 1}" ${d.page <= 1 ? 'disabled' : ''}>← Anterior</button>`;
        html += `<span class="page-info">Página ${d.page} de ${d.total_pages} · ${d.total} registros</span>`;
        html += `<select class="input per-page-select" data-per-page aria-label="Itens por página">
            ${[10, 20, 50, 100].map((n) => `<option value="${n}" ${n === this.state.per_page ? 'selected' : ''}>${n}/pág</option>`).join('')}
        </select>`;
        html += `<button type="button" data-page="${d.page + 1}" ${d.page >= d.total_pages ? 'disabled' : ''}>Próxima →</button>`;
        html += '</div>';
        return html;
    }

    render() {
        this.el.innerHTML = this.skeleton();
        this.load();
    }
}

function formatColumnLabel(key) {
    const labels = {
        codigo_interno: 'Código',
        descricao: 'Descrição',
        estoque_atual: 'Saldo',
        estoque_minimo: 'Mínimo',
        preco_venda: 'Preço',
        localizacao: 'Local',
        cliente_nome: 'Cliente',
        cpf_cnpj: 'CPF/CNPJ',
        telefone: 'Telefone',
        cidade: 'Cidade',
        placa: 'Placa',
        status: 'Status',
        created_at: 'Data',
        numero: 'Número',
        nome: 'Nome',
    };
    return labels[key] || key.replace(/_/g, ' ');
}

function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function optionsSearchId(el) {
    if (el.id === 'tabela-clientes') return '#busca-clientes';
    if (el.id === 'tabela-estoque') return '#busca-pecas';
    if (el.id === 'tabela-orcamentos') return '#busca-orc';
    if (el.id === 'tabela-os') return '#busca-os';
    if (el.id === 'tabela-fornecedores') return '#busca-forn';
    if (el.id === 'tabela-usuarios') return '#busca-users';
    if (el.id === 'tabela-servicos') return '#busca-servicos';
    return null;
}

window.DataTable = DataTable;
window.escapeHtml = escapeHtml;
