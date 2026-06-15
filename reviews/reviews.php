<?php
require_once '../db.class.php';
require_once '../auth.php';
verificarAdmin();
$mensagem = "";
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

/* Corrigido aqui: adicionado r.usuario_id e r.filme_id para o JavaScript de edição funcionar perfeitamente */
$stmtReviews = $conn->prepare(
    "SELECT r.id, r.conteudo, r.nota, r.data_hora, r.usuario_id, r.filme_id,
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
    <link rel="stylesheet" href="../css/style_home.css">
    <link rel="stylesheet" href="../css/style_reviews.css">
</head>
<body>

<?php include '../header.php'; ?>

<?php if ($mensagem): ?>
<div class="toast-loc <?= $tipoMensagem ?>" id="toastMsg">
    <i class="bi bi-<?= $tipoMensagem === 'success' ? 'check-circle-fill' : ($tipoMensagem === 'danger' ? 'x-circle-fill' : 'exclamation-triangle-fill') ?>"></i>
    <?= htmlspecialchars($mensagem) ?>
</div>
<script>setTimeout(() => { const t = document.getElementById('toastMsg'); if(t) t.style.opacity='0'; }, 3500);</script>
<?php endif; ?>

<div class="container py-2">

    <div class="hero">
        <h1>Reviews</h1>
        <p><?= $totalReviews ?> avaliação<?= $totalReviews !== 1 ? 'ões' : '' ?> registrada<?= $totalReviews !== 1 ? 's' : '' ?></p>
    </div>

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

    <?php if (count($reviews) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-chat-square-text"></i>
        <p>Nenhuma review encontrada.</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($reviews as $r): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="filme-card h-full">
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

<?php include 'reviews_form.php'; ?>



<?php include '../footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>