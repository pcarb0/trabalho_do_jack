<div class="modal fade" id="modalCrud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="list.php">
                <input type="hidden" name="id_edicao" id="id_edicao" value="0">
                <div class="modal-header" style="border-bottom: 1px solid var(--card-border);">
                    <h5 class="modal-title" id="modalTitulo" style="color: var(--text); font-family: 'Cinzel', serif;">Novo Usuário</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="nome" id="f_nome" class="form-control" placeholder="Ex: João Silva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nome de Usuário (Username) *</label>
                            <input type="text" name="username" id="f_username" class="form-control" placeholder="Ex: joaosilva" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" id="f_email" class="form-control" placeholder="Ex: joao@email.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de Telefone</label>
                            <input type="text" name="numero" id="f_numero" class="form-control" placeholder="Ex: (49) 99999-9999">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" id="label_senha">Senha *</label>
                            <input type="password" name="senha" id="f_senha" class="form-control" placeholder="Digite a senha">
                            <small class="text-muted d-none" id="aviso_senha" style="font-size: 0.78rem;">Deixe em branco para manter a senha atual.</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-center pt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="administrador" id="f_admin" value="1">
                                <label class="form-check-label" for="f_admin" style="color: var(--text); font-weight: 600;">Definir como Administrador</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-salvar"><i class="bi bi-floppy me-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalNovo() {
    document.getElementById('modalTitulo').textContent = 'Novo Usuário';
    document.getElementById('id_edicao').value  = '0';
    document.getElementById('f_nome').value     = '';
    document.getElementById('f_username').value = '';
    document.getElementById('f_email').value    = '';
    document.getElementById('f_numero').value   = '';
    
    const fSenha = document.getElementById('f_senha');
    fSenha.value = '';
    fSenha.required = true; // Obrigatório no cadastro
    document.getElementById('aviso_senha').classList.add('d-none');
    
    document.getElementById('f_admin').checked  = false;
}

function abrirModalEditar(u) {
    document.getElementById('modalTitulo').textContent = 'Editar Usuário';
    document.getElementById('id_edicao').value  = u.id;
    document.getElementById('f_nome').value     = u.nome     || '';
    document.getElementById('f_username').value = u.username || '';
    document.getElementById('f_email').value    = u.email    || '';
    document.getElementById('f_numero').value   = u.numero   || '';
    
    const fSenha = document.getElementById('f_senha');
    fSenha.value = '';
    fSenha.required = false; // Opcional na edição
    document.getElementById('aviso_senha').classList.remove('d-none');
    
    document.getElementById('f_admin').checked  = parseInt(u.adminstrador) === 1;
}
</script>