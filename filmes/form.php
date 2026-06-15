<div class="modal fade" id="modalCrud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="list.php">
                <input type="hidden" name="id_edicao" id="id_edicao" value="0">
                <div class="modal-header" style="border-bottom: 1px solid var(--card-border);">
                    <h5 class="modal-title" id="modalTitulo" style="color: var(--text); font-family: 'Cinzel', serif;">Novo Filme</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Título do Filme *</label>
                            <input type="text" name="titulo" id="f_titulo" class="form-control" placeholder="Ex: Interstellar" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ano de Publicação *</label>
                            <input type="number" name="ano_publicacao" id="f_ano" class="form-control" placeholder="2014" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diretor *</label>
                            <input type="text" name="diretor" id="f_diretor" class="form-control" placeholder="Christopher Nolan" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gênero *</label>
                            <input type="text" name="genero" id="f_genero" class="form-control" placeholder="Ficção Científica" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">URL do Poster *</label>
                            <input type="text" name="poster_url" id="f_poster" class="form-control" placeholder="https://link-da-imagem.jpg" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sinopse *</label>
                            <textarea name="sinopse" id="f_sinopse" class="form-control" rows="4" placeholder="Escreva o resumo da história aqui..." required></textarea>
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
    document.getElementById('modalTitulo').textContent = 'Novo Filme';
    document.getElementById('id_edicao').value  = '0';
    document.getElementById('f_titulo').value   = '';
    document.getElementById('f_diretor').value  = '';
    document.getElementById('f_ano').value      = '';
    document.getElementById('f_genero').value   = '';
    document.getElementById('f_sinopse').value  = '';
    document.getElementById('f_poster').value   = '';
}

function abrirModalEditar(filme) {
    document.getElementById('modalTitulo').textContent = 'Editar Filme';
    document.getElementById('id_edicao').value  = filme.id;
    document.getElementById('f_titulo').value   = filme.titulo    || '';
    document.getElementById('f_diretor').value  = filme.diretor   || '';
    document.getElementById('f_ano').value      = filme.ano_publicacao || '';
    document.getElementById('f_genero').value   = filme.genero    || '';
    document.getElementById('f_sinopse').value  = filme.sinopse   || '';
    document.getElementById('f_poster').value   = filme.poster_url || '';
}

<?php if (isset($filmeEdicao) && $filmeEdicao): ?>
window.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalCrud'));
    abrirModalEditar(<?= json_encode($filmeEdicao) ?>);
    modal.show();
});
<?php endif; ?>
</script>