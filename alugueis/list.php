<?php
// Puxa a conexão centralizada voltando uma pasta para a raiz
require_once '../db.class.php';
 
$mensagem = "";
$tipoMensagem = "";

require_once '../auth.php'; // Verifica se está logado
verificarAdmin();
 
// ─── Daqui para baixo o seu código continua exatamente igual ───────────────
    // ... restando do seu código nativo executando com a variável $conn normalmente

// ─── DELETE ───────────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM alugueis WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $mensagem     = "Aluguel excluído com sucesso!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem     = "Erro ao excluir: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// ─── DEVOLVER ─────────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'devolver' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $conn->prepare("UPDATE alugueis SET data_devolucao = NOW() WHERE id = :id AND data_devolucao IS NULL");
        $stmt->execute([':id' => $id]);
        $mensagem     = "Devolução registrada!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem     = "Erro ao devolver: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// ─── CREATE / UPDATE (POST) ──────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario_id      = (int)($_POST['usuario_id']      ?? 0);
    $filme_id        = (int)($_POST['filme_id']        ?? 0);
    $data_aluguel    = trim($_POST['data_aluguel']     ?? '');
    $data_devolucao  = trim($_POST['data_devolucao']   ?? '');
    $id_edicao       = (int)($_POST['id_edicao']       ?? 0);

    $devData = ($data_devolucao !== '') ? $data_devolucao : null;

    if ($usuario_id <= 0 || $filme_id <= 0 || $data_aluguel === '') {
        $mensagem     = "Usuário, filme e data de aluguel são obrigatórios.";
        $tipoMensagem = "warning";
    } else {
        try {
            if ($id_edicao > 0) {
                $stmt = $conn->prepare(
                    "UPDATE alugueis SET usuario_id=:uid, filme_id=:fid,
                     data_aluguel=:da, data_devolucao=:dd WHERE id=:id"
                );
                $stmt->execute([
                    ':uid' => $usuario_id,
                    ':fid' => $filme_id,
                    ':da'  => $data_aluguel,
                    ':dd'  => $devData,
                    ':id'  => $id_edicao,
                ]);
                $mensagem = "Aluguel atualizado com sucesso!";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO alugueis (usuario_id, filme_id, data_aluguel, data_devolucao)
                     VALUES (:uid, :fid, :da, :dd)"
                );
                $stmt->execute([
                    ':uid' => $usuario_id,
                    ':fid' => $filme_id,
                    ':da'  => $data_aluguel,
                    ':dd'  => $devData,
                ]);
                $mensagem = "Aluguel registrado com sucesso!";
            }
            $tipoMensagem = "success";
        } catch (PDOException $e) {
            $mensagem     = "Erro: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }
}

// ─── Buscar aluguel para edição ───────────────────────────────────────────────
$aluguelEdicao = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM alugueis WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $aluguelEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ─── Listas auxiliares para os select boxes ──────────────────────────────────
$usuarios = $conn->query("SELECT id, username FROM usuario ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$filmes   = $conn->query("SELECT id, titulo FROM filmes ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);

// ─── Filtro de status ─────────────────────────────────────────────────────────
$filtroStatus = $_GET['status'] ?? 'todos';

// ─── Busca + Paginação ────────────────────────────────────────────────────────
$porPagina = 6;
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$offset    = ($pagina - 1) * $porPagina;
$busca     = trim($_GET['busca'] ?? '');

$condicoes  = [];
$paramsBusca = [];

if ($busca !== '') {
    $condicoes[]  = "(f.titulo LIKE :busca OR u.username LIKE :busca2)";
    $paramsBusca[':busca']  = "%$busca%";
    $paramsBusca[':busca2'] = "%$busca%";
}
if ($filtroStatus === 'ativos') {
    $condicoes[] = "a.data_devolucao IS NULL";
} elseif ($filtroStatus === 'devolvidos') {
    $condicoes[] = "a.data_devolucao IS NOT NULL";
}

$whereBusca = count($condicoes) ? "WHERE " . implode(" AND ", $condicoes) : "";

$stmtTotal = $conn->prepare(
    "SELECT COUNT(*) FROM alugueis a
     JOIN usuario u ON u.id = a.usuario_id
     JOIN filmes  f ON f.id = a.filme_id
     $whereBusca"
);
$stmtTotal->execute($paramsBusca);
$totalAlugueis = (int)$stmtTotal->fetchColumn();
$totalPaginas  = max(1, (int)ceil($totalAlugueis / $porPagina));

$stmtAlugueis = $conn->prepare(
    "SELECT a.id, a.data_aluguel, a.data_devolucao, a.usuario_id, a.filme_id,
            u.username, f.titulo AS filme_titulo
     FROM alugueis a
     JOIN usuario u ON u.id = a.usuario_id
     JOIN filmes  f ON f.id = a.filme_id
     $whereBusca
     ORDER BY a.data_aluguel DESC
     LIMIT :lim OFFSET :off"
);
foreach ($paramsBusca as $k => $v) $stmtAlugueis->bindValue($k, $v);
$stmtAlugueis->bindValue(':lim', $porPagina, PDO::PARAM_INT);
$stmtAlugueis->bindValue(':off', $offset,    PDO::PARAM_INT);
$stmtAlugueis->execute();
$alugueis = $stmtAlugueis->fetchAll(PDO::FETCH_ASSOC);

// Contadores do painel
$contAtivos     = (int)$conn->query("SELECT COUNT(*) FROM alugueis WHERE data_devolucao IS NULL")->fetchColumn();
$contDevolvidos = (int)$conn->query("SELECT COUNT(*) FROM alugueis WHERE data_devolucao IS NOT NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locadora — Listagem de Aluguéis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style_home.css">
    <link rel="stylesheet" href="../css/style_reviews.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
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
        <h1>Aluguéis</h1>
        <p><?= $totalAlugueis ?> registro<?= $totalAlugueis !== 1 ? 's' : '' ?> encontrado<?= $totalAlugueis !== 1 ? 's' : '' ?></p>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num"><?= $contAtivos ?></div>
                <div class="stat-label"><i class="bi bi-hourglass-split me-1"></i>Em aberto</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num"><?= $contDevolvidos ?></div>
                <div class="stat-label"><i class="bi bi-check2-circle me-1"></i>Devolvidos</div>
            </div>
        </div>
    </div>

    <div class="toolbar" style="flex-wrap:wrap;gap:10px;">
        <form method="GET" class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" name="busca" class="search-input"
                   placeholder="Buscar por filme ou usuário…"
                   value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filtroStatus) ?>">
        </form>

        <div class="d-flex gap-2 flex-wrap">
            <a href="?status=todos&busca=<?= urlencode($busca) ?>" class="filter-pill <?= $filtroStatus === 'todos' ? 'active' : '' ?>">
               <i class="bi bi-list-ul"></i> Todos
            </a>
            <a href="?status=ativos&busca=<?= urlencode($busca) ?>" class="filter-pill <?= $filtroStatus === 'ativos' ? 'active' : '' ?>">
               <i class="bi bi-hourglass-split"></i> Em aberto
            </a>
            <a href="?status=devolvidos&busca=<?= urlencode($busca) ?>" class="filter-pill <?= $filtroStatus === 'devolvidos' ? 'active' : '' ?>">
               <i class="bi bi-check2-circle"></i> Devolvidos
            </a>
        </div>

        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalNovo()">
            <i class="bi bi-plus-lg me-1"></i> Novo Aluguel
        </button>
    </div>

    <?php if (count($alugueis) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-camera-video"></i>
        <p>Nenhum aluguel encontrado.</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($alugueis as $a): $devolvido = !empty($a['data_devolucao']); ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="filme-card review-card h-100">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="fw-bold" style="color:var(--text);font-size:.97rem;">
                        <?= htmlspecialchars($a['filme_titulo']) ?>
                    </div>
                    <?php if ($devolvido): ?>
                        <span class="status-badge status-devolvido"><i class="bi bi-check2-circle"></i> Devolvido</span>
                    <?php else: ?>
                        <span class="status-badge status-ativo"><i class="bi bi-hourglass-split"></i> Em aberto</span>
                    <?php endif; ?>
                </div>

                <div class="aluguel-meta mt-2">
                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($a['username']) ?>
                </div>
                <div class="aluguel-meta mt-1" style="font-size: 0.82rem; color: var(--text-muted);">
                    <i class="bi bi-calendar-event me-1"></i>
                    Aluguel: <?= date('d/m/Y H:i', strtotime($a['data_aluguel'])) ?>
                </div>
                <?php if ($devolvido): ?>
                <div class="aluguel-meta mt-1" style="font-size: 0.82rem; color: var(--text-muted);">
                    <i class="bi bi-calendar-check me-1"></i>
                    Devolução: <?= date('d/m/Y H:i', strtotime($a['data_devolucao'])) ?>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <?php if (!$devolvido): ?>
                    <a href="list.php?action=devolver&id=<?= $a['id'] ?>&status=<?= $filtroStatus ?>&busca=<?= urlencode($busca) ?>" class="btn-devolver text-decoration-none"
                       onclick="return confirm('Confirmar devolução?')">
                        <i class="bi bi-box-arrow-in-left me-1"></i>Devolver
                    </a>
                    <?php endif; ?>
                    
                        <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#modalCrud"
                            onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($a)) ?>)">
                            <i class="bi bi-pencil-square me-1"></i>Editar
                        </button>
                    
                    <button class="btn-delete" data-bs-toggle="modal" data-bs-target="#modalDelete"
                        onclick="confirmarDelete(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['filme_titulo'])) ?>)">
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
                <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&busca=<?= urlencode($busca) ?>&status=<?= $filtroStatus ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>&status=<?= $filtroStatus ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&busca=<?= urlencode($busca) ?>&status=<?= $filtroStatus ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<div class="modal fade modal-delete" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body py-4 px-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size:2.4rem;color:#e74c3c;"></i>
                <h5 class="mt-3 mb-1" style="color:var(--text);">Excluir aluguel?</h5>
                <p style="color:var(--text-muted);font-size:.88rem;" id="deleteNome" class="mb-4"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <a id="linkDelete" href="#" class="btn-salvar" style="background:linear-gradient(135deg,#c0392b,#e74c3c);padding:10px 24px;border-radius:50px;color:#fff;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;">
                        <i class="bi bi-trash3 me-1"></i>Excluir
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'form.php'; ?>

<?php include '../footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarDelete(id, titulo) {
    document.getElementById('deleteNome').textContent = '"' + titulo + '"';
    document.getElementById('linkDelete').href = 'list.php?action=delete&id=' + id + '&status=<?= $filtroStatus ?>&busca=<?= urlencode($busca) ?>';
}
</script>
</body>
</html>