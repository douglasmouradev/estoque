<div class="grid-2">

    <section class="card">

        <div class="card-header"><h2>Parâmetros</h2></div>

        <form id="form-config">

            <label>Dias sem movimentação (peças paradas)

                <input type="number" name="pecas_paradas_dias" id="pecas_paradas_dias" min="1" max="3650" required>

            </label>

            <p class="muted" style="font-size:0.82rem;margin:0.5rem 0 1rem">Usado no relatório de peças sem giro.</p>

            <button type="submit" class="btn btn-primary">Salvar</button>

        </form>

    </section>

    <section class="card">

        <div class="card-header"><h2>Dados da oficina (PDF)</h2></div>

        <form id="form-oficina" class="form-grid">

            <label class="span-2">Nome <input name="oficina_nome" id="oficina_nome"></label>

            <label>CNPJ <input name="oficina_cnpj" id="oficina_cnpj"></label>

            <label>Telefone <input name="oficina_telefone" id="oficina_telefone"></label>

            <label>E-mail <input name="oficina_email" id="oficina_email"></label>

            <label class="span-2">Endereço <input name="oficina_endereco" id="oficina_endereco"></label>

            <button type="submit" class="btn btn-primary span-2">Salvar dados</button>

        </form>

    </section>

</div>



<section class="card" id="categorias" style="margin-top:1rem">

    <div class="card-header">

        <h2>Categorias de peças</h2>

        <button type="button" class="btn btn-primary btn-write btn-sm" id="btn-nova-categoria">+ Nova categoria</button>

    </div>

    <div id="lista-categorias" class="is-loading"></div>

</section>



<dialog id="modal-categoria">

    <form id="form-categoria">

        <h2>Categoria</h2>

        <input type="hidden" id="categoria-edit-id">

        <label>Nome <input name="nome" id="categoria-nome" class="input" required></label>

        <footer>

            <button type="button" class="btn" data-close>Cancelar</button>

            <button type="submit" class="btn btn-primary">Salvar</button>

        </footer>

    </form>

</dialog>



<script src="<?= htmlspecialchars($asset('assets/js/categorias.js')) ?>"></script>

<script>

(async () => {

    const res = await API.get('/config');

    const d = res.dados;

    if (d.pecas_paradas_dias) document.getElementById('pecas_paradas_dias').value = d.pecas_paradas_dias;

    if (d.oficina) {

        Object.entries({ oficina_nome:'nome', oficina_cnpj:'cnpj', oficina_telefone:'telefone', oficina_email:'email', oficina_endereco:'endereco' }).forEach(([id, k]) => {

            const el = document.getElementById(id);

            if (el && d.oficina[k]) el.value = d.oficina[k];

        });

    }

    const cnpjEl = document.getElementById('oficina_cnpj');

    if (cnpjEl) Masks.bindCpfCnpj(cnpjEl);

    document.getElementById('form-config').addEventListener('submit', async (e) => {

        e.preventDefault();

        await API.post('/config', Object.fromEntries(new FormData(e.target)));

        Toast.success('Configurações salvas');

    });

    document.getElementById('form-oficina').addEventListener('submit', async (e) => {

        e.preventDefault();

        const body = Object.fromEntries(new FormData(e.target));

        body.pecas_paradas_dias = document.getElementById('pecas_paradas_dias').value;

        await API.post('/config', body);

        Toast.success('Dados da oficina salvos');

    });

})();

</script>

