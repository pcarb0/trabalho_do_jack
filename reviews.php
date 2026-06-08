<?php
// ─── Conexão ─────────────────────────────────────────────────────────────────
try {
    $conn = new PDO("mysql:host=127.0.0.1;dbname=trabalho;charset=utf8", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

$mensagem     = "";
$tipoMensagem = "";

// ─── DELETE ───────────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $mensagem     = "Review excluída com sucesso!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem     = "Erro ao excluir: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// ─── CREATE / UPDATE ─────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario_id = (int)($_POST['usuario_id'] ?? 0);
    $filme_id   = (int)($_POST['filme_id']   ?? 0);
    $conteudo   = trim($_POST['conteudo']    ?? '');
    $nota       = (int)($_POST['nota']       ?? 0);
    $id_edicao  = (int)($_POST['id_edicao']  ?? 0);

    if ($usuario_id <= 0 || $filme_id <= 0 || $conteudo === '' || $nota < 1 || $nota > 10) {
        $mensagem     = "Preencha todos os campos. A nota deve ser entre 1 e 10.";
        $tipoMensagem = "warning";
    } else {
        try {
            if ($id_edicao > 0) {
                $stmt = $conn->prepare(
                    "UPDATE reviews SET usuario_id=:uid, filme_id=:fid, conteudo=:con, nota=:nota
                     WHERE id=:id"
                );
                $stmt->execute([
                    ':uid'  => $usuario_id,
                    ':fid'  => $filme_id,
                    ':con'  => $conteudo,
                    ':nota' => $nota,
                    ':id'   => $id_edicao,
                ]);
                $mensagem = "Review atualizada com sucesso!";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO reviews (usuario_id, filme_id, conteudo, nota)
                     VALUES (:uid, :fid, :con, :nota)"
                );
                $stmt->execute([
                    ':uid'  => $usuario_id,
                    ':fid'  => $filme_id,
                    ':con'  => $conteudo,
                    ':nota' => $nota,
                ]);
                $mensagem = "Review cadastrada com sucesso!";
            }
            $tipoMensagem = "success";
        } catch (PDOException $e) {
            $mensagem     = "Erro: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }
}

// ─── Buscar review para edição ────────────────────────────────────────────────
$reviewEdicao = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $reviewEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ─── Listas auxiliares para os selects ───────────────────────────────────────
$usuarios = $conn->query("SELECT id, username FROM usuario ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$filmes   = $conn->query("SELECT id, titulo FROM filmes ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);

// ─── Busca + Paginação ────────────────────────────────────────────────────────
$porPagina = 6;
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$offset    = ($pagina - 1) * $porPagina;
$busca     = trim($_GET['busca'] ?? '');

$paramsBusca = [];
$whereBusca  = "";
if ($busca !== '') {
    $whereBusca  = "WHERE f.titulo LIKE :busca OR u.username LIKE :busca2";
    $paramsBusca = [':busca' => "%$busca%", ':busca2' => "%$busca%"];
}

$stmtTotal = $conn->prepare(
    "SELECT COUNT(*) FROM reviews r
     JOIN usuario u ON u.id = r.usuario_id
     JOIN filmes  f ON f.id = r.filme_id
     $whereBusca"
);
$stmtTotal->execute($paramsBusca);
$totalReviews = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalReviews / $porPagina));

$stmtReviews = $conn->prepare(
    "SELECT r.id, r.conteudo, r.nota, r.data_hora,
            u.username, f.titulo AS filme_titulo
     FROM reviews r
     JOIN usuario u ON u.id = r.usuario_id
     JOIN filmes  f ON f.id = r.filme_id
     $whereBusca
     ORDER BY r.data_hora DESC
     LIMIT :lim OFFSET :off"
);
foreach ($paramsBusca as $k => $v) $stmtReviews->bindValue($k, $v);
$stmtReviews->bindValue(':lim', $porPagina, PDO::PARAM_INT);
$stmtReviews->bindValue(':off', $offset,    PDO::PARAM_INT);
$stmtReviews->execute();
$reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locadora — Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_home.css">
    <style>
        .nota-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: linear-gradient(135deg, var(--purple), var(--purple-lt));
            color: #fff;
            font-weight: 700;
            font-size: .82rem;
            padding: 3px 10px;
            border-radius: 50px;
        }
        .review-card {
            background: #0d0b1a;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 18px 20px;
            transition: transform .2s, box-shadow .2s;
        }
        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(0,0,0,.45);
        }
        .review-meta {
            font-size: .78rem;
            color: var(--text-muted);
        }
        .review-conteudo {
            color: var(--text);
            font-size: .92rem;
            margin-top: 8px;
            line-height: 1.55;
        }
        .star-nota { color: #f0c040; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Toast -->
<?php if ($mensagem): ?>
<div class="toast-loc <?= $tipoMensagem ?>" id="toastMsg">
    <i class="bi bi-<?= $tipoMensagem === 'success' ? 'check-circle-fill' : ($tipoMensagem === 'danger' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
    <?= htmlspecialchars($mensagem) ?>
</div>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.style.opacity='0'; }, 3500);</script>
<?php endif; ?>

<div class="container py-2">

    <!-- Hero -->
    <div class="hero">
        <h1>Reviews</h1>
        <p><?= $totalReviews ?> avaliação<?= $totalReviews !== 1 ? 'ões' : '' ?> registrada<?= $totalReviews !== 1 ? 's' : '' ?></p>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
        <form method="GET" class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" name="busca" class="search-input"
                   placeholder="Buscar por filme ou usuário…"
                   value="<?= htmlspecialchars($busca) ?>">
        </form>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalNovo()">
            <i class="bi bi-plus-lg me-1"></i> Nova Review
        </button>
    </div>

    <!-- Lista de reviews -->
    <?php if (count($reviews) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-chat-square-text"></i>
        <p>Nenhuma review encontrada.</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($reviews as $r): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="review-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-bold" style="color:var(--text);font-size:.97rem;">
                            <?= htmlspecialchars($r['filme_titulo']) ?>
                        </div>
                        <div class="review-meta mt-1">
                            <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($r['username']) ?>
                            &nbsp;·&nbsp;
                            <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($r['data_hora'])) ?>
                        </div>
                    </div>
                    <span class="nota-badge"><i class="bi bi-star-fill star-nota"></i><?= $r['nota'] ?>/10</span>
                </div>

                <p class="review-conteudo"><?= nl2br(htmlspecialchars($r['conteudo'])) ?></p>

                <div class="d-flex gap-2 mt-3">
                    <button class="btn-edit"
                        data-bs-toggle="modal" data-bs-target="#modalCrud"
                        onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($r)) ?>)">
                        <i class="bi bi-pencil-square me-1"></i>Editar
                    </button>
                    <button class="btn-delete"
                        data-bs-toggle="modal" data-bs-target="#modalDelete"
                        onclick="confirmarDelete(<?= $r['id'] ?>, <?= htmlspecialchars(json_encode($r['filme_titulo'])) ?>)">
                        <i class="bi bi-trash3 me-1"></i>Excluir
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
    <nav class="d-flex justify-content-center mb-4 mt-3">
        <ul class="pagination">
            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busca=<?= urlencode($busca) ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busca=<?= urlencode($busca) ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- ══ MODAL CRUD ════════════════════════════════════════════════════════════ -->
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

<!-- ══ MODAL DELETE ══════════════════════════════════════════════════════════ -->
<div class="modal fade modal-delete" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body py-4 px-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:2.4rem;color:#e74c3c;"></i>
                <h5 class="mt-3 mb-1" style="color:var(--text);">Excluir review?</h5>
                <p style="color:var(--text-muted);font-size:.88rem;" id="deleteNome" class="mb-4"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <a id="linkDelete" href="#" class="btn-salvar"
                       style="background:linear-gradient(135deg,#c0392b,#e74c3c);padding:10px 24px;border-radius:50px;color:#fff;font-weight:700;text-decoration:none;">
                        <i class="bi bi-trash3 me-1"></i>Excluir
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer>
    Bloco Buster &copy; <?= date('Y') ?> — Desenvolvido com <span>♥</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

<?php if ($reviewEdicao): ?>
window.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalCrud'));
    abrirModalEditar(<?= json_encode($reviewEdicao) ?>);
    modal.show();
});
<?php endif; ?>
</script>
</body>
</html>
