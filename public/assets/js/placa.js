/** Máscara placa Mercosul (ABC1D23) e padrão antigo (ABC-1234) */
function mascaraPlaca(valor) {
    const v = valor.toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (v.length <= 3) return v;
    // Mercosul: 4º char pode ser letra ou número na posição 4
    if (/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/.test(v.slice(0, 7)) || v.length > 4 && /^[A-Z]{3}[0-9]/.test(v)) {
        return v.slice(0, 3) + v.slice(3, 4) + (v[4] ? v[4] : '') + (v.slice(5, 7) || '');
    }
    if (v.length <= 7) return v.slice(0, 3) + '-' + v.slice(3, 7);
    return v.slice(0, 3) + '-' + v.slice(3, 7);
}

function bindPlacaInput(input) {
    if (!input) return;
    input.addEventListener('input', () => {
        const pos = input.selectionStart;
        input.value = mascaraPlaca(input.value);
    });
}

window.mascaraPlaca = mascaraPlaca;
window.bindPlacaInput = bindPlacaInput;
