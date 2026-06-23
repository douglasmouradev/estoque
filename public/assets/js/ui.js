const UI = {
    confirm(msg, titulo = 'Confirmar') {
        return new Promise((resolve) => {
            const dlg = document.getElementById('modal-confirm');
            if (!dlg) {
                resolve(window.confirm(msg));
                return;
            }
            dlg.querySelector('[data-confirm-title]').textContent = titulo;
            dlg.querySelector('[data-confirm-msg]').textContent = msg;
            dlg.showModal();
            const okBtn = dlg.querySelector('[data-confirm-ok]');
            okBtn.focus();
            const ok = () => { cleanup(); resolve(true); };
            const no = () => { cleanup(); resolve(false); };
            const cleanup = () => {
                dlg.close();
                okBtn.removeEventListener('click', ok);
                dlg.querySelector('[data-confirm-cancel]').removeEventListener('click', no);
            };
            okBtn.addEventListener('click', ok);
            dlg.querySelector('[data-confirm-cancel]').addEventListener('click', no);
        });
    },
    prompt(msg, titulo = 'Informe', valor = '') {
        return new Promise((resolve) => {
            const dlg = document.getElementById('modal-prompt');
            if (!dlg) {
                resolve(window.prompt(msg, valor));
                return;
            }
            dlg.querySelector('[data-prompt-title]').textContent = titulo;
            dlg.querySelector('[data-prompt-msg]').textContent = msg;
            const inp = dlg.querySelector('[data-prompt-input]');
            inp.value = valor;
            dlg.showModal();
            inp.focus();
            const ok = () => { const v = inp.value; cleanup(); resolve(v); };
            const no = () => { cleanup(); resolve(null); };
            const cleanup = () => {
                dlg.close();
                dlg.querySelector('[data-prompt-ok]').removeEventListener('click', ok);
                dlg.querySelector('[data-prompt-cancel]').removeEventListener('click', no);
            };
            dlg.querySelector('[data-prompt-ok]').addEventListener('click', ok);
            dlg.querySelector('[data-prompt-cancel]').addEventListener('click', no);
        });
    },
    relatorio(titulo, linhas) {
        return new Promise((resolve) => {
            const dlg = document.getElementById('modal-relatorio');
            if (!dlg) {
                alert(`${titulo}\n\n${linhas.join('\n')}`);
                resolve();
                return;
            }
            dlg.querySelector('[data-relatorio-title]').textContent = titulo;
            const body = dlg.querySelector('[data-relatorio-body]');
            if (!linhas.length) {
                body.innerHTML = '<p class="muted">Nenhum item a exibir.</p>';
            } else {
                body.innerHTML = `<ul class="relatorio-list">${linhas.map((l) => `<li>${escapeHtml(l)}</li>`).join('')}</ul>`;
            }
            dlg.showModal();
            const fechar = () => { dlg.close(); btn.removeEventListener('click', fechar); resolve(); };
            const btn = dlg.querySelector('[data-relatorio-ok]');
            btn.addEventListener('click', fechar);
        });
    },
    pdfPreview(url, titulo = 'Visualizar PDF') {
        const dlg = document.getElementById('modal-pdf');
        if (!dlg) { window.open(url, '_blank'); return; }
        dlg.querySelector('[data-pdf-title]').textContent = titulo;
        dlg.querySelector('[data-pdf-frame]').src = url;
        dlg.showModal();
    },
    autocomplete(input, fetchFn, onSelect) {
        let box;
        let timer;
        let items = [];
        let active = -1;

        const close = () => { box?.remove(); box = null; items = []; active = -1; };
        const render = () => {
            close();
            if (!items.length) return;
            box = document.createElement('ul');
            box.className = 'autocomplete-list';
            box.setAttribute('role', 'listbox');
            items.forEach((item, i) => {
                const li = document.createElement('li');
                li.textContent = item.label || item.nome || item.descricao;
                li.setAttribute('role', 'option');
                if (i === active) li.classList.add('is-active');
                li.addEventListener('mousedown', (e) => { e.preventDefault(); onSelect(item); close(); });
                box.appendChild(li);
            });
            const wrap = input.closest('.autocomplete') || input.parentElement;
            wrap.classList.add('autocomplete');
            wrap.appendChild(box);
        };
        const pick = (i) => {
            if (items[i]) { onSelect(items[i]); close(); }
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            active = -1;
            const q = input.value.trim();
            if (q.length < 2) { close(); return; }
            timer = setTimeout(async () => {
                items = await fetchFn(q);
                render();
            }, 250);
        });
        input.addEventListener('keydown', (e) => {
            if (!box) return;
            if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); render(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
            else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); pick(active); }
            else if (e.key === 'Escape') close();
        });
        document.addEventListener('click', (e) => {
            if (box && !box.contains(e.target) && e.target !== input) close();
        });
    },
};

window.UI = UI;
