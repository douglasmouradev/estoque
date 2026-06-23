<div class="toolbar">
    <input type="search" id="busca-users" class="input input-search" placeholder="Nome ou e-mail...">
    <button type="button" class="btn btn-primary" id="btn-novo-user">+ Novo usuário</button>
</div>
<div id="tabela-usuarios" data-endpoint="/usuarios"></div>

<dialog id="modal-usuario">
    <form id="form-usuario">
        <h2>Usuário</h2>
        <input type="hidden" name="id" id="user-id">
        <div class="form-grid">
            <label>Nome <input name="nome" required></label>
            <label>E-mail <input type="email" name="email" required></label>
            <label id="user-senha-req">Senha <input type="password" name="senha"></label>
            <label>Perfil
                <select name="perfil">
                    <option value="mecanico">Mecânico</option>
                    <option value="gerente">Gerente</option>
                    <option value="admin">Administrador</option>
                </select>
            </label>
            <label class="checkbox span-2"><input type="checkbox" name="ativo" checked> Ativo</label>
        </div>
        <footer>
            <button type="button" class="btn" data-close>Cancelar</button>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </footer>
    </form>
</dialog>
<script src="<?= htmlspecialchars($asset('assets/js/tables.js')) ?>"></script>
<script src="<?= htmlspecialchars($asset('assets/js/usuarios.js')) ?>"></script>
