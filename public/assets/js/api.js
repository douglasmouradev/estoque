const API = {
    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },
    async request(method, url, body = null, opts = {}) {
        const local = opts.local;
        if (!local) Spinner.show();
        if (local) local.classList.add('is-loading');
        const fetchOpts = {
            method,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': this.csrf(),
            },
            credentials: 'same-origin',
        };
        if (body && !(body instanceof FormData)) {
            fetchOpts.headers['Content-Type'] = 'application/json';
            fetchOpts.body = JSON.stringify({ ...body, _token: this.csrf() });
        } else if (body instanceof FormData) {
            body.append('_token', this.csrf());
            fetchOpts.body = body;
        } else if (body) {
            fetchOpts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
            const p = new URLSearchParams(body);
            p.append('_token', this.csrf());
            fetchOpts.body = p.toString();
        }
        try {
            const res = await fetch(url, fetchOpts);
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.sucesso === false) {
                const msg = data.erro || 'Erro na requisição';
                if (data.erros) Toast.error(Object.values(data.erros).join(' '));
                else Toast.error(msg);
                throw new Error(msg);
            }
            return data;
        } finally {
            if (!local) Spinner.hide();
            if (local) local.classList.remove('is-loading');
        }
    },
    get(url, opts) { return this.request('GET', url, null, opts); },
    post(url, body, opts) { return this.request('POST', url, body, opts); },
    put(url, body, opts) { return this.request('PUT', url, body, opts); },
    delete(url, opts) { return this.request('DELETE', url, null, opts); },
};
