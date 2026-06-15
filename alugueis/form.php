<div class="modal fade" id="modalCrud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="list.php">
                <input type="hidden" name="id_edicao" id="id_edicao" value="0">
                <div class="modal-header" style="border-bottom:1px solid var(--card-border);">
                    <h5 class="modal-title" id="modalTitulo" style="color:var(--text);font-family:'Cinzel',serif;">Novo Aluguel</h5>
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
                        <div class="col-md-6">
                            <label class="form-label">Data do Aluguel *</label>
                            <input type="datetime-local" name="data_aluguel" id="f_data_aluguel" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data de Devolução <span class="text-muted" style="font-size:.8rem;">(opcional)</span></label>
                            <input type="datetime-local" name="data_devolucao" id="f_data_devolucao" class="form-control">
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
    document.getElementById('modalTitulo').textContent = 'Novo Aluguel';
    document.getElementById('id_edicao').value        = '0';
    document.getElementById('f_usuario').value       = '';
    document.getElementById('f_filme').value         = '';
    document.getElementById('f_data_aluguel').value  = '';
    document.getElementById('f_data_devolucao').value = '';
}

function abrirModalEditar(a) {
    document.getElementById('modalTitulo').textContent = 'Editar Aluguel';
    document.getElementById('id_edicao').value        = a.id;
    document.getElementById('f_usuario').value       = a.usuario_id;
    document.getElementById('f_filme').value         = a.filme_id;
    
    // Converte datas no formato SQL "AAAA-MM-DD HH:MM:SS" para o formato do input datetime-local "AAAA-MM-DDTHH:MM"
    document.getElementById('f_data_aluguel').value   = a.data_aluguel   ? a.data_aluguel.replace(' ', 'T').slice(0, 16)   : '';
    document.getElementById('f_data_devolucao').value = a.data_devolucao ? a.data_devolucao.replace(' ', 'T').slice(0, 16) : '';
}

<?php if (isset($aluguelEdicao) && $aluguelEdicao): ?>
window.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalCrud'));
    abrirModalEditar(<?= json_encode($aluguelEdicao) ?>);
    modal.show();
});
<?php endif; ?>
</script>