const Toast = {
    show(msg, type = 'info', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;
        container.setAttribute('aria-live', 'polite');
        const el = document.createElement('div');
        el.className = `toast toast-${type}`;
        el.textContent = msg;
        container.appendChild(el);
        let timer = setTimeout(() => el.remove(), duration);
        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', () => { timer = setTimeout(() => el.remove(), 1500); });
    },
    success(msg) { this.show(msg, 'success'); },
    error(msg) { this.show(msg, 'error'); },
    info(msg) { this.show(msg, 'info'); },
};

const Spinner = {
    el: null,
    show() {
        this.el = document.getElementById('spinner');
        if (this.el) this.el.classList.remove('hidden');
    },
    hide() {
        if (this.el) this.el.classList.add('hidden');
    },
};

document.querySelectorAll('[data-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const dlg = btn.closest('dialog');
        if (dlg?.id === 'modal-pdf') {
            dlg.querySelector('[data-pdf-frame]')?.removeAttribute('src');
        }
        dlg?.close();
    });
});

document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
        const search = document.querySelector('.input-search');
        if (search) { e.preventDefault(); search.focus(); }
    }
});

document.getElementById('menu-toggle')?.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-open');
});

document.querySelector('.sidebar-backdrop')?.addEventListener('click', () => {
    document.body.classList.remove('sidebar-open');
});
