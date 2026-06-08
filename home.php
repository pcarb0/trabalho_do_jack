<?php
// ─── Conexão com o banco de dados ────────────────────────────────────────────
try {
    $conn = new PDO("mysql:host=127.0.0.1;dbname=trabalho;charset=utf8", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}
 
$mensagem = "";
$tipoMensagem = "";
 
// ─── CRUD: Deletar Filme ──────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM filmes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $mensagem = "Filme excluído com sucesso!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}
 
// ─── CRUD: Criar / Editar Filme ───────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titulo        = trim($_POST['titulo'] ?? '');
    $diretor       = trim($_POST['diretor'] ?? '');
    $ano           = (int)($_POST['ano_publicacao'] ?? 0);
    $genero        = trim($_POST['genero'] ?? '');
    $sinopse       = trim($_POST['sinopse'] ?? '');
    $poster_url    = trim($_POST['poster_url'] ?? '');
    $id_edicao     = (int)($_POST['id_edicao'] ?? 0);
 
    if ($titulo === '') {
        $mensagem = "O título é obrigatório.";
        $tipoMensagem = "warning";
    } else {
        try {
            if ($id_edicao > 0) {
                // UPDATE
                $stmt = $conn->prepare("UPDATE filmes SET titulo=:titulo, diretor=:diretor, ano_publicacao=:ano, genero=:genero, sinopse=:sinopse, poster_url=:poster WHERE id=:id");
                $stmt->execute([
                    ':titulo'   => $titulo,
                    ':diretor'  => $diretor,
                    ':ano'      => $ano,
                    ':genero'   => $genero,
                    ':sinopse'  => $sinopse,
                    ':poster'   => $poster_url,
                    ':id'       => $id_edicao,
                ]);
                $mensagem = "Filme atualizado com sucesso!";
            } else {
                // INSERT
                $stmt = $conn->prepare("INSERT INTO filmes (titulo, diretor, ano_publicacao, genero, sinopse, poster_url) VALUES (:titulo, :diretor, :ano, :genero, :sinopse, :poster)");
                $stmt->execute([
                    ':titulo'   => $titulo,
                    ':diretor'  => $diretor,
                    ':ano'      => $ano,
                    ':genero'   => $genero,
                    ':sinopse'  => $sinopse,
                    ':poster'   => $poster_url,
                ]);
                $mensagem = "Filme cadastrado com sucesso!";
            }
            $tipoMensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }
}
 
// ─── Buscar filme para edição ─────────────────────────────────────────────────
$filmeEdicao = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM filmes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $filmeEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
}
 
// ─── Busca + Paginação ────────────────────────────────────────────────────────
$porPagina   = 6;
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$offset      = ($pagina - 1) * $porPagina;
$busca       = trim($_GET['busca'] ?? '');
 
$paramsBusca = [];
$whereBusca  = "";
if ($busca !== '') {
    $whereBusca = "WHERE titulo LIKE :busca OR genero LIKE :busca2";
    $paramsBusca = [':busca' => "%$busca%", ':busca2' => "%$busca%"];
}
 
// Total de filmes
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM filmes $whereBusca");
$stmtTotal->execute($paramsBusca);
$totalFilmes = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalFilmes / $porPagina));
 
// Filmes da página atual
$stmtFilmes = $conn->prepare("SELECT id, titulo, genero, ano_publicacao, poster_url, diretor FROM filmes $whereBusca ORDER BY id DESC LIMIT :limit OFFSET :offset");
foreach ($paramsBusca as $k => $v) $stmtFilmes->bindValue($k, $v);
$stmtFilmes->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
$stmtFilmes->bindValue(':offset', $offset,    PDO::PARAM_INT);
$stmtFilmes->execute();
$filmes = $stmtFilmes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locadora — Catálogo</title>
 
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
 

</head>
<body>
 
<!-- ══ NAVBAR ══════════════════════════════════════════════════════════════ -->
<?php include 'header.php'; ?>
 
<!-- ══ TOAST ═══════════════════════════════════════════════════════════════ -->
<?php if ($mensagem): ?>
<div class="toast-loc <?= $tipoMensagem ?>" id="toastMsg">
    <i class="bi bi-<?= $tipoMensagem === 'success' ? 'check-circle-fill' : ($tipoMensagem === 'danger' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
    <?= htmlspecialchars($mensagem) ?>
</div>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.style.opacity = '0'; }, 3500);</script>
<?php endif; ?>
 
<!-- ══ CONTEÚDO PRINCIPAL ═══════════════════════════════════════════════════ -->
<div class="container py-2">
 
    <!-- Hero -->
    <div class="hero">
        <h1>Catálogo de Filmes</h1>
        <p><?= $totalFilmes ?> título<?= $totalFilmes !== 1 ? 's' : '' ?> disponíve<?= $totalFilmes !== 1 ? 'is' : 'l' ?></p>
    </div>
 
    <!-- Toolbar -->
    <div class="toolbar">
        <form method="GET" class="search-wrapper">
            <i class="bi bi-search"></i>
            <input
                type="text"
                name="busca"
                class="search-input"
                placeholder="Buscar por título ou gênero…"
                value="<?= htmlspecialchars($busca) ?>"
            >
        </form>
 
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalNovo()">
            <i class="bi bi-plus-lg me-1"></i> Novo Filme
        </button>
    </div>
 
    <!-- Grid de filmes -->
    <?php if (count($filmes) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-film"></i>
        <h3><?= $busca ? 'Nenhum resultado encontrado' : 'Nenhum filme cadastrado' ?></h3>
        <p><?= $busca ? 'Tente outro termo de busca.' : 'Adicione o primeiro filme ao catálogo.' ?></p>
    </div>
    <?php else: ?>
    <div class="filmes-grid">
        <?php foreach ($filmes as $f): ?>
        <?php
            $poster = !empty($f['poster_url'])
                ? (filter_var($f['poster_url'], FILTER_VALIDATE_URL) ? $f['poster_url'] : "poster_filmes/" . $f['poster_url'])
                : "poster_filmes/sem-foto.png";
        ?>
        <div class="filme-card">
            <div class="poster-wrap">
                <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($f['titulo']) ?>" loading="lazy">
                <div class="poster-overlay">
                    <button
                        class="btn-overlay btn-edit-ov"
                        onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($f)) ?>)"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCrud"
                    ><i class="bi bi-pencil"></i></button>
                    <button
                        class="btn-overlay btn-del-ov"
                        onclick="confirmarDelete(<?= $f['id'] ?>, '<?= addslashes($f['titulo']) ?>')"
                        data-bs-toggle="modal"
                        data-bs-target="#modalDelete"
                    ><i class="bi bi-trash3"></i></button>
                    <a href="alugar.php?id=<?= $f['id'] ?>" class="btn-overlay btn-rent-ov"><i class="bi bi-bag"></i></a>
                </div>
            </div>
            <div class="card-body-loc">
                <div class="card-titulo"><?= htmlspecialchars($f['titulo']) ?></div>
                <div class="card-meta">
                    <?= htmlspecialchars($f['genero'] ?: '—') ?>
                    <?php if ($f['ano_publicacao']): ?> · <?= $f['ano_publicacao'] ?><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
 
    <!-- ── Paginação Bootstrap ── -->
    <?php if ($totalPaginas > 1): ?>
    <nav aria-label="Paginação" class="d-flex justify-content-center mb-4">
        <ul class="pagination">
            <!-- Anterior -->
            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busca=<?= urlencode($busca) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
 
            <?php
            // Janela deslizante: mostra até 5 páginas
            $janela = 2;
            $inicio = max(1, $pagina - $janela);
            $fim    = min($totalPaginas, $pagina + $janela);
 
            if ($inicio > 1): ?>
                <li class="page-item"><a class="page-link" href="?pagina=1&busca=<?= urlencode($busca) ?>">1</a></li>
                <?php if ($inicio > 2): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
            <?php endif; ?>
 
            <?php for ($p = $inicio; $p <= $fim; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
 
            <?php if ($fim < $totalPaginas): ?>
                <?php if ($fim < $totalPaginas - 1): ?>
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                <?php endif; ?>
                <li class="page-item"><a class="page-link" href="?pagina=<?= $totalPaginas ?>&busca=<?= urlencode($busca) ?>"><?= $totalPaginas ?></a></li>
            <?php endif; ?>
 
            <!-- Próxima -->
            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busca=<?= urlencode($busca) ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>
 
<!-- ══ MODAL CRUD (Criar / Editar) ════════════════════════════════════════ -->
<div class="modal fade" id="modalCrud" tabindex="-1" aria-labelledby="modalCrudLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="home.php">
                <input type="hidden" name="id_edicao" id="id_edicao" value="0">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Título *</label>
                            <input type="text" name="titulo" id="f_titulo" class="form-control" placeholder="Ex.: Interestelar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diretor</label>
                            <input type="text" name="diretor" id="f_diretor" class="form-control" placeholder="Ex.: Christopher Nolan">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ano</label>
                            <input type="number" name="ano_publicacao" id="f_ano" class="form-control" placeholder="2024" min="1888" max="2100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gênero</label>
                            <input type="text" name="genero" id="f_genero" class="form-control" placeholder="Ex.: Ficção">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Sinopse</label>
                            <textarea name="sinopse" id="f_sinopse" class="form-control" placeholder="Breve descrição do filme…"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">URL ou nome do pôster</label>
                            <input type="text" name="poster_url" id="f_poster" class="form-control" placeholder="https://... ou nome-do-arquivo.jpg">
                            <div class="form-text" style="color:var(--text-muted);font-size:.78rem;">
                                Use URL externa completa ou nome do arquivo dentro de <code style="color:var(--purple-lt)">poster_filmes/</code>
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
 
<!-- ══ MODAL DELETE ════════════════════════════════════════════════════════ -->
<div class="modal fade modal-delete" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body py-4 px-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:2.4rem;color:#e74c3c;"></i>
                <h5 class="mt-3 mb-1" style="font-family: 'Lucida Sans', 'Lucida Sans Regular', 'Lucida Grande', 'Lucida Sans Unicode', Geneva, Verdana, sans-serif;,serif;color:var(--text);">Excluir filme?</h5>
                <p style="color:var(--text-muted);font-size:.88rem;" id="deleteNome" class="mb-4"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <a id="linkDelete" href="#" class="btn-salvar" style="background:linear-gradient(135deg,#c0392b,#e74c3c);padding:10px 24px;border-radius:50px;color:#fff;font-weight:700;text-decoration:none;">
                        <i class="bi bi-trash3 me-1"></i>Excluir
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet " href="css/style_home.css">
 
<!-- ══ RODAPÉ ══════════════════════════════════════════════════════════════ -->
<footer>
    Bloco Buster &copy; <?= date('Y') ?> — Desenvolvido com <span>♥</span>
</footer>
 
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
 
<script>
    // ── Modal: Novo Filme ──────────────────────────────────────────────────
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
 
    // ── Modal: Editar Filme ────────────────────────────────────────────────
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
 
    // ── Modal: Confirmar Delete ────────────────────────────────────────────
    function confirmarDelete(id, titulo) {
        document.getElementById('deleteNome').textContent = '"' + titulo + '"';
        document.getElementById('linkDelete').href = 'home.php?action=delete&id=' + id;
    }
 
    <?php if ($filmeEdicao): ?>
    // Abrir modal de edição se veio via GET
    window.addEventListener('DOMContentLoaded', function() {
        const modal = new bootstrap.Modal(document.getElementById('modalCrud'));
        abrirModalEditar(<?= json_encode($filmeEdicao) ?>);
        modal.show();
    });
    <?php endif; ?>
</script>
</body>
</html>