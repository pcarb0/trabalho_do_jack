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
        $stmt = $conn->prepare("DELETE FROM alugueis WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $mensagem     = "Aluguel excluído com sucesso!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem     = "Erro ao excluir: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}

// ─── DEVOLVER (registra data_devolucao = agora) ───────────────────────────────
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

// ─── CREATE / UPDATE ─────────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario_id      = (int)($_POST['usuario_id']      ?? 0);
    $filme_id        = (int)($_POST['filme_id']        ?? 0);
    $data_aluguel    = trim($_POST['data_aluguel']     ?? '');
    $data_devolucao  = trim($_POST['data_devolucao']   ?? '');
    $id_edicao       = (int)($_POST['id_edicao']       ?? 0);

    // data_devolucao é opcional (pode ser NULL)
    $devData = ($data_devolucao !== '') ? $data_devolucao : null;

    if ($usuario_id <= 0 || $filme_id <= 0 || $data_aluguel === '') {
        $mensagem     = "Usuário, filme e data do aluguel são obrigatórios.";
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

// ─── Listas auxiliares ────────────────────────────────────────────────────────
$usuarios = $conn->query("SELECT id, username FROM usuario ORDER BY username")->fetchAll(PDO::FETCH_ASSOC);
$filmes   = $conn->query("SELECT id, titulo FROM filmes ORDER BY titulo")->fetchAll(PDO::FETCH_ASSOC);

// ─── Filtro de status ─────────────────────────────────────────────────────────
$filtroStatus = $_GET['status'] ?? 'todos'; // todos | ativos | devolvidos

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
    "SELECT a.id, a.data_aluguel, a.data_devolucao,
            u.id AS usuario_id, u.username,
            f.id AS filme_id, f.titulo AS filme_titulo
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

// Contadores para o painel
$contAtivos     = (int)$conn->query("SELECT COUNT(*) FROM alugueis WHERE data_devolucao IS NULL")->fetchColumn();
$contDevolvidos = (int)$conn->query("SELECT COUNT(*) FROM alugueis WHERE data_devolucao IS NOT NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locadora — Aluguéis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style_home.css">
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
            font-size: .78rem;
            padding: 3px 11px;
            border-radius: 50px;
        }
        .status-ativo     { background: rgba(39,174,96,.18); color: #2ecc71; border: 1px solid rgba(39,174,96,.3); }
        .status-devolvido { background: rgba(149,165,166,.15); color: #95a5a6; border: 1px solid rgba(149,165,166,.25); }

        .aluguel-card {
            background: #0d0b1a;
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 18px 20px;
            transition: transform .2s, box-shadow .2s;
        }
        .aluguel-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(0,0,0,.45);
        }
        .aluguel-meta { font-size: .78rem; color: var(--text-muted); }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 18px 22px;
            text-align: center;
        }
        .stat-card .stat-num {
            font-size: 2rem;
            font-weight: 800;
            font-family: 'Cinzel', serif;
            background: linear-gradient(135deg, var(--purple), var(--purple-lt));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-card .stat-label { font-size: .8rem; color: var(--text-muted); margin-top: 2px; }

        .filter-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 18px;
            border-radius: 50px;
            font-size: .84rem;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid var(--card-border);
            background: var(--card-bg);
            color: var(--text-muted);
            text-decoration: none;
            transition: all .18s;
        }
        .filter-pill:hover, .filter-pill.active {
            background: linear-gradient(135deg, var(--purple), var(--purple-lt));
            color: #fff;
            border-color: transparent;
        }
        .btn-devolver {
            background: linear-gradient(135deg,#1a7a4a,#2ecc71);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 6px 16px;
            font-size: .8rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s;
            text-decoration: none;
        }
        .btn-devolver:hover { opacity: .85; color:#fff; }
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
        <h1>Aluguéis</h1>
        <p><?= $totalAlugueis ?> registro<?= $totalAlugueis !== 1 ? 's' : '' ?> encontrado<?= $totalAlugueis !== 1 ? 's' : '' ?></p>
    </div>

    <!-- Painéis de stat -->
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

    <!-- Toolbar -->
    <div class="toolbar" style="flex-wrap:wrap;gap:10px;">
        <form method="GET" class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" name="busca" class="search-input"
                   placeholder="Buscar por filme ou usuário…"
                   value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filtroStatus) ?>">
        </form>

        <!-- Filtros de status -->
        <div class="d-flex gap-2 flex-wrap">
            <a href="?status=todos&busca=<?= urlencode($busca) ?>"
               class="filter-pill <?= $filtroStatus === 'todos'      ? 'active' : '' ?>">
               <i class="bi bi-list-ul"></i> Todos
            </a>
            <a href="?status=ativos&busca=<?= urlencode($busca) ?>"
               class="filter-pill <?= $filtroStatus === 'ativos'     ? 'active' : '' ?>">
               <i class="bi bi-hourglass-split"></i> Em aberto
            </a>
            <a href="?status=devolvidos&busca=<?= urlencode($busca) ?>"
               class="filter-pill <?= $filtroStatus === 'devolvidos' ? 'active' : '' ?>">
               <i class="bi bi-check2-circle"></i> Devolvidos
            </a>
        </div>

        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalNovo()">
            <i class="bi bi-plus-lg me-1"></i> Novo Aluguel
        </button>
    </div>

    <!-- Cards de aluguel -->
    <?php if (count($alugueis) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-camera-video"></i>
        <p>Nenhum aluguel encontrado.</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($alugueis as $a):
            $devolvido = !empty($a['data_devolucao']);
        ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="aluguel-card h-100">
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
                <div class="aluguel-meta mt-1">
                    <i class="bi bi-calendar-event me-1"></i>
                    Aluguel: <?= date('d/m/Y H:i', strtotime($a['data_aluguel'])) ?>
                </div>
                <?php if ($devolvido): ?>
                <div class="aluguel-meta mt-1">
                    <i class="bi bi-calendar-check me-1"></i>
                    Devolução: <?= date('d/m/Y H:i', strtotime($a['data_devolucao'])) ?>
                </div>
                <?php endif; ?>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <?php if (!$devolvido): ?>
                    <a href="alugueis.php?action=devolver&id=<?= $a['id'] ?>" class="btn-devolver"
                       onclick="return confirm('Confirmar devolução?')">
                        <i class="bi bi-box-arrow-in-left me-1"></i>Devolver
                    </a>
                    <?php endif; ?>
                    <button class="btn-edit"
                        data-bs-toggle="modal" data-bs-target="#modalCrud"
                        onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($a)) ?>)">
                        <i class="bi bi-pencil-square me-1"></i>Editar
                    </button>
                    <button class="btn-delete"
                        data-bs-toggle="modal" data-bs-target="#modalDelete"
                        onclick="confirmarDelete(<?= $a['id'] ?>, <?= htmlspecialchars(json_encode($a['filme_titulo'])) ?>)">
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

<!-- ══ MODAL CRUD ════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalCrud" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="alugueis.php">
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
                            <input type="datetime-local" name="data_aluguel" id="f_data_aluguel"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data de Devolução <span style="color:var(--text-muted);font-size:.8rem;">(opcional)</span></label>
                            <input type="datetime-local" name="data_devolucao" id="f_data_devolucao"
                                   class="form-control">
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
                <h5 class="mt-3 mb-1" style="color:var(--text);">Excluir aluguel?</h5>
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
// Formata datetime para o input datetime-local (YYYY-MM-DDTHH:MM)
function toDatetimeLocal(str) {
    if (!str) return '';
    return str.slice(0, 16); // "2024-01-15 20:30:00" → "2024-01-15T20:30" não funciona direto
    // MySQL retorna "2024-01-15 20:30:00", input precisa "2024-01-15T20:30"
}

function abrirModalNovo() {
    document.getElementById('modalTitulo').textContent      = 'Novo Aluguel';
    document.getElementById('id_edicao').value              = '0';
    document.getElementById('f_usuario').value             = '';
    document.getElementById('f_filme').value               = '';
    document.getElementById('f_data_aluguel').value        = '';
    document.getElementById('f_data_devolucao').value      = '';
}

function abrirModalEditar(a) {
    document.getElementById('modalTitulo').textContent      = 'Editar Aluguel';
    document.getElementById('id_edicao').value              = a.id;
    document.getElementById('f_usuario').value             = a.usuario_id;
    document.getElementById('f_filme').value               = a.filme_id;
    // Converte "YYYY-MM-DD HH:MM:SS" → "YYYY-MM-DDTHH:MM"
    document.getElementById('f_data_aluguel').value        = a.data_aluguel   ? a.data_aluguel.replace(' ', 'T').slice(0, 16)   : '';
    document.getElementById('f_data_devolucao').value      = a.data_devolucao ? a.data_devolucao.replace(' ', 'T').slice(0, 16) : '';
}

function confirmarDelete(id, titulo) {
    document.getElementById('deleteNome').textContent = '"' + titulo + '"';
    document.getElementById('linkDelete').href = 'alugueis.php?action=delete&id=' + id;
}

<?php if ($aluguelEdicao): ?>
window.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalCrud'));
    abrirModalEditar(<?= json_encode($aluguelEdicao) ?>);
    modal.show();
});
<?php endif; ?>
</script>
</body>
</html>
