const Format = {
    moeda(v) {
        const n = parseFloat(v) || 0;
        return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    },
    data(v) {
        if (!v) return '—';
        const d = new Date(v);
        return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleDateString('pt-BR');
    },
    dataHora(v) {
        if (!v) return '—';
        const d = new Date(v);
        return Number.isNaN(d.getTime()) ? String(v) : d.toLocaleString('pt-BR');
    },
    statusOrcamento(s) {
        const map = {
            rascunho: 'neutral', enviado: 'info', aprovado: 'success',
            reprovado: 'danger', convertido: 'success',
        };
        const labels = {
            rascunho: 'Rascunho', enviado: 'Aguardando cliente', aprovado: 'Aprovado',
            reprovado: 'Reprovado', convertido: 'Convertido em OS',
        };
        const c = map[s] || 'neutral';
        return `<span class="badge badge-${c}">${labels[s] || escapeHtml(s)}</span>`;
    },
    statusOs(s) {
        const labels = {
            aberta: 'Aberta', em_andamento: 'Em andamento', aguardando_peca: 'Aguardando peça',
            finalizada: 'Finalizada', cancelada: 'Cancelada',
        };
        return `<span class="badge badge-neutral">${labels[s] || escapeHtml((s || '').replace(/_/g, ' '))}</span>`;
    },
    calcularTotaisOrcamento(itens, descGeralPct, descGeralVal) {
        let subtotal = 0;
        (itens || []).forEach((item) => {
            const bruto = (parseFloat(item.quantidade) || 0) * (parseFloat(item.preco_unitario) || 0);
            const desc = Math.max(
                parseFloat(item.desconto_valor) || 0,
                bruto * ((parseFloat(item.desconto_percent) || 0) / 100)
            );
            subtotal += bruto - desc;
        });
        const descGeral = Math.max(parseFloat(descGeralVal) || 0, subtotal * ((parseFloat(descGeralPct) || 0) / 100));
        const total = Math.max(0, subtotal - descGeral);
        return {
            subtotal: Math.round(subtotal * 100) / 100,
            desconto_geral: Math.round(descGeral * 100) / 100,
            total: Math.round(total * 100) / 100,
        };
    },
    stepperOrcamento(status) {
        const steps = [
            { key: 'rascunho', label: 'Rascunho' },
            { key: 'enviado', label: 'Enviado' },
            { key: 'aprovado', label: 'Aprovado' },
            { key: 'convertido', label: 'OS' },
        ];
        const order = ['rascunho', 'enviado', 'aprovado', 'convertido'];
        let current = status;
        if (status === 'reprovado') current = 'enviado';
        const idx = order.indexOf(current);
        let html = '<div class="stepper" role="list">';
        steps.forEach((s, i) => {
            let cls = 'stepper-step';
            if (i < idx) cls += ' is-done';
            else if (s.key === current || (status === 'convertido' && s.key === 'convertido')) cls += ' is-active';
            else if (status === 'reprovado' && s.key === 'enviado') cls += ' is-reprovado';
            html += `<div class="${cls}" role="listitem"><span class="stepper-dot">${i + 1}</span><span>${s.label}</span></div>`;
            if (i < steps.length - 1) html += `<div class="stepper-line${i < idx ? ' is-done' : ''}"></div>`;
        });
        html += '</div>';
        if (status === 'reprovado') html += '<p class="stepper-note text-danger">Orçamento reprovado pelo cliente</p>';
        return html;
    },
    stepperOs(status) {
        const steps = [
            { key: 'aberta', label: 'Aberta' },
            { key: 'em_andamento', label: 'Em andamento' },
            { key: 'aguardando_peca', label: 'Aguard. peça' },
            { key: 'finalizada', label: 'Finalizada' },
        ];
        const order = ['aberta', 'em_andamento', 'aguardando_peca', 'finalizada'];
        if (status === 'cancelada') {
            return `<div class="stepper"><span class="badge badge-danger">OS cancelada</span></div>`;
        }
        const idx = Math.max(0, order.indexOf(status));
        let html = '<div class="stepper" role="list">';
        steps.forEach((s, i) => {
            let cls = 'stepper-step';
            if (i < idx) cls += ' is-done';
            if (s.key === status) cls += ' is-active';
            html += `<div class="${cls}" role="listitem"><span class="stepper-dot">${i + 1}</span><span>${s.label}</span></div>`;
            if (i < steps.length - 1) html += `<div class="stepper-line${i < idx ? ' is-done' : ''}"></div>`;
        });
        return html + '</div>';
    },
};

function escapeHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

window.Format = Format;
window.escapeHtml = escapeHtml;
