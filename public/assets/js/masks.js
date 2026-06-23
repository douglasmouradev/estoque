const Masks = {
    soDigitos(v) { return (v || '').replace(/\D/g, ''); },

    cpfCnpj(v) {
        const d = this.soDigitos(v).slice(0, 14);
        if (d.length <= 11) {
            return d.replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        return d.replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    },

    cep(v) {
        const d = this.soDigitos(v).slice(0, 8);
        return d.replace(/(\d{5})(\d)/, '$1-$2');
    },

    placa(v) {
        return typeof mascaraPlaca === 'function' ? mascaraPlaca(v) : v.toUpperCase();
    },

    bindCpfCnpj(input) {
        if (!input) return;
        input.addEventListener('input', () => { input.value = this.cpfCnpj(input.value); });
    },

    bindCep(input) {
        if (!input) return;
        input.addEventListener('input', () => { input.value = this.cep(input.value); });
    },

    validarCpfCnpj(v) {
        const d = this.soDigitos(v);
        if (d.length === 11) return this._cpf(d);
        if (d.length === 14) return this._cnpj(d);
        return false;
    },

    _cpf(cpf) {
        if (/^(\d)\1+$/.test(cpf)) return false;
        for (let t = 9; t < 11; t++) {
            let s = 0;
            for (let i = 0; i < t; i++) s += parseInt(cpf[i], 10) * (t + 1 - i);
            if (((10 * s) % 11) % 10 !== parseInt(cpf[t], 10)) return false;
        }
        return true;
    },

    _cnpj(cnpj) {
        if (/^(\d)\1+$/.test(cnpj)) return false;
        const calc = (base, pesos) => {
            let s = 0;
            pesos.forEach((p, i) => { s += parseInt(base[i], 10) * p; });
            const r = s % 11;
            return r < 2 ? 0 : 11 - r;
        };
        const p1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        const p2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        return calc(cnpj, p1) === parseInt(cnpj[12], 10) && calc(cnpj, p2) === parseInt(cnpj[13], 10);
    },

    marcarInvalido(input, invalido) {
        input.classList.toggle('input-invalid', invalido);
    },
};

window.Masks = Masks;
