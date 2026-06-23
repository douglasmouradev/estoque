(function () {
    const d = window.__relData || {};
    if (typeof Chart !== 'undefined') {
        const fat = document.getElementById('chart-faturamento');
        if (fat) {
            new Chart(fat, {
                type: 'bar',
                data: {
                    labels: ['Faturamento'],
                    datasets: [{ label: 'R$', data: [parseFloat(d.faturamento) || 0], backgroundColor: '#2563eb' }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });
        }
        const mec = document.getElementById('chart-mecanicos');
        const rows = d.mecanicos || [];
        if (mec && rows.length) {
            new Chart(mec, {
                type: 'bar',
                data: {
                    labels: rows.map((r) => r.nome),
                    datasets: [{ label: 'Horas', data: rows.map((r) => parseFloat(r.total_horas)), backgroundColor: '#16a34a' }],
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
            });
        }
    }
    document.getElementById('btn-filtrar-rel')?.addEventListener('click', () => {
        const de = document.getElementById('rel-de')?.value;
        const ate = document.getElementById('rel-ate')?.value;
        location.href = `/relatorios?de=${encodeURIComponent(de)}&ate=${encodeURIComponent(ate)}`;
    });
    const updExport = (id, tipo) => {
        document.getElementById(id)?.addEventListener('click', (e) => {
            e.preventDefault();
            const de = document.getElementById('rel-de')?.value;
            const ate = document.getElementById('rel-ate')?.value;
            location.href = `/relatorios/exportar?tipo=${tipo}&de=${encodeURIComponent(de)}&ate=${encodeURIComponent(ate)}`;
        });
    };
    updExport('btn-export-estoque', 'estoque');
    updExport('btn-export-mec', 'mecanicos');
})();
