const tabela = new DataTable('#tabela-auditoria', {
    defaultSort: 'created_at',
    columns: ['created_at', 'user_nome', 'acao', 'entidade', 'entidade_id'],
    mapRow(row) {
        return `<td>${Format.dataHora(row.created_at)}</td>
            <td>${escapeHtml(row.user_nome || '—')}</td>
            <td>${escapeHtml(row.acao)}</td>
            <td>${escapeHtml(row.entidade)}</td>
            <td>${row.entidade_id ?? '—'}</td>`;
    },
});
