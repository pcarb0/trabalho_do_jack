<div class="modal fade" id="modalCrud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="reviews.php">
                <input type="hidden" name="id_edicao" id="id_edicao" value="0">
                <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                    <h5 class="modal-title" id="modalTitulo" style="color:var(--text);font-family:'Cinzel',serif;">Nova Review</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Usuário *</label>
                            <select name="usuario_id" id="f_usuario" class="form-select" required>
                                <option value="">Selecione…</option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Filme *</label>
                            <select name="filme_id" id="f_filme" class="form-select" required>
                                <option value="">Selecione…</option>
                                <?php foreach ($filmes as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['titulo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nota (1–10) *</label>
                            <input type="number" name="nota" id="f_nota" class="form-control"
                                   min="1" max="10" placeholder="8" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Conteúdo da review *</label>
                            <textarea name="conteudo" id="f_conteudo" class="form-control" rows="4"
                                      placeholder="Escreva sua opinião sobre o filme…" required></textarea>
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

<div class="modal fade modal-delete" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body py-4 px-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:2.4rem;color:#e74c3c;"></i>
                <h5 class="mt-3 mb-1" style="color:var(--text);">Excluir review?</h5>
                <p style="color:var(--text-muted);font-size:.88rem;" id="deleteNome" class="mb-4"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <a id="linkDelete" href="#" class="btn-salvar btn-confirm-delete">
                        <i class="bi bi-trash3 me-1"></i>Excluir
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalNovo() {
    document.getElementById('modalTitulo').textContent = 'Nova Review';
    document.getElementById('id_edicao').value   = '0';
    document.getElementById('f_usuario').value  = '';
    document.getElementById('f_filme').value    = '';
    document.getElementById('f_nota').value     = '';
    document.getElementById('f_conteudo').value = '';
}

function abrirModalEditar(r) {
    document.getElementById('modalTitulo').textContent = 'Editar Review';
    document.getElementById('id_edicao').value   = r.id;
    document.getElementById('f_usuario').value  = r.usuario_id;
    document.getElementById('f_filme').value    = r.filme_id;
    document.getElementById('f_nota').value     = r.nota;
    document.getElementById('f_conteudo').value = r.conteudo;
}

function confirmarDelete(id, titulo) {
    document.getElementById('deleteNome').textContent = 'Review de "' + titulo + '"';
    document.getElementById('linkDelete').href = 'reviews.php?action=delete&id=' + id;
}

<?php if (isset($reviewEdicao) && $reviewEdicao): ?>
window.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalCrud'));
    abrirModalEditar(<?= json_encode($reviewEdicao) ?>);
    modal.show();
});
<?php endif; ?>
</script>