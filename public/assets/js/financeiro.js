const tabela = new DataTable('#tabela-financeiro', {
    defaultSort: 'finalizada_em',
    columns: ['numero', 'cliente_nome', 'placa', 'valor_total', 'valor_pago', 'saldo', 'status_pagamento', ''],
    mapRow(row) {
        return `<td><a href="/os/${row.id}">#${row.numero}</a></td>
            <td>${escapeHtml(row.cliente_nome)}</td>
            <td>${escapeHtml(row.placa)}</td>
            <td>${Format.moeda(row.valor_total)}</td>
            <td>${Format.moeda(row.valor_pago)}</td>
            <td><strong>${Format.moeda(row.saldo)}</strong></td>
            <td>${escapeHtml(row.status_pagamento)}</td>
            <td><a href="/os/${row.id}" class="btn btn-sm">Ver OS</a></td>`;
    },
    onLoaded(d) {
        const el = document.getElementById('fin-total-pendente');
        if (el) el.textContent = Format.moeda(d.total_pendente);
    },
});
