<?php
// Puxa a conexão centralizada voltando uma pasta para a raiz
require_once '../db.class.php';
require_once '../auth.php'; // Verifica se está logado
verificarAdmin();
$mensagem = "";
$tipoMensagem = "";
 
// ─── CRUD: Deletar Filme ──────────────────────────────────────────────────────
// ─── CRUD: Deletar Filme Corrigido ──────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Passo 1: Apaga primeiro todas as reviews vinculadas a esse filme
        $stmtReviews = $conn->prepare("DELETE FROM reviews WHERE filme_id = :id");
        $stmtReviews->execute([':id' => $id]);

        // Passo 2: Agora sim, apaga o filme sem estourar o erro de restrição
        $stmtFilme = $conn->prepare("DELETE FROM filmes WHERE id = :id");
        $stmtFilme->execute([':id' => $id]);
        
        $mensagem = "Filme e suas reviews associadas foram excluídos com sucesso!";
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
    $ano_publicacao= (int)($_POST['ano_publicacao'] ?? 0);
    $genero        = trim($_POST['genero'] ?? '');
    $sinopse       = trim($_POST['sinopse'] ?? '');
    $poster_url    = trim($_POST['poster_url'] ?? '');
    $id_edicao     = (int)($_POST['id_edicao'] ?? 0);
 
    if ($titulo === '' || $diretor === '' || $ano_publicacao <= 0 || $genero === '' || $sinopse === '' || $poster_url === '') {
        $mensagem = "Por favor, preencha todos os campos obrigatórios (*).";
        $tipoMensagem = "warning";
    } else {
        try {
            if ($id_edicao > 0) {
                $stmt = $conn->prepare(
                    "UPDATE filmes 
                     SET titulo = :titulo, diretor = :diretor, ano_publicacao = :ano, genero = :genero, sinopse = :sinopse, poster_url = :poster 
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':diretor'=> $diretor,
                    ':ano'    => $ano_publicacao,
                    ':genero' => $genero,
                    ':sinopse'=> $sinopse,
                    ':poster' => $poster_url,
                    ':id'     => $id_edicao
                ]);
                $mensagem = "Filme atualizado com sucesso!";
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO filmes (titulo, diretor, ano_publicacao, genero, sinopse, poster_url) 
                     VALUES (:titulo, :diretor, :ano, :genero, :sinopse, :poster)"
                );
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':diretor'=> $diretor,
                    ':ano'    => $ano_publicacao,
                    ':genero' => $genero,
                    ':sinopse'=> $sinopse,
                    ':poster' => $poster_url
                ]);
                $mensagem = "Filme cadastrado com sucesso!";
            }
            $tipoMensagem = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao salvar os dados: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }
}
 
// ─── Buscar Filme para Edição (GET) ──────────────────────────────────────────
$filmeEdicao = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM filmes WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $filmeEdicao = $stmt->fetch(PDO::FETCH_ASSOC);
}
 
// ─── Configurações de Paginação e Filtros ─────────────────────────────────────
$porPagina = 6; 
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaActual - 1) * $porPagina;
 
$busca = trim($_GET['busca'] ?? '');
$paramsBusca = [];
$whereBusca = "";
 
if ($busca !== '') {
    $whereBusca = "WHERE titulo LIKE :busca OR diretor LIKE :busca2 OR genero LIKE :busca3";
    $paramsBusca = [
        ':busca'  => "%$busca%",
        ':busca2' => "%$busca%",
        ':busca3' => "%$busca%"
    ];
}
 
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM filmes $whereBusca");
$stmtTotal->execute($paramsBusca);
$totalFilmes = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalFilmes / $porPagina));
 
$stmtFilmes = $conn->prepare("SELECT * FROM filmes $whereBusca ORDER BY id DESC LIMIT :limite OFFSET :offset");
foreach ($paramsBusca as $chave => $valor) {
    $stmtFilmes->bindValue($chave, $valor);
}
$stmtFilmes->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtFilmes->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtFilmes->execute();
$filmes = $stmtFilmes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locadora — Catálogo de Filmes</title>
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
<script>
    setTimeout(() => {
        const toast = document.getElementById('toastMsg');
        if (toast) toast.style.opacity = '0';
    }, 3500);
</script>
<?php endif; ?>
 
<div class="container py-2">
 
    <div class="hero">
        <h1>Catálogo de Filmes</h1>
        <p><?= $totalFilmes ?> filme<?= $totalFilmes !== 1 ? 's' : '' ?> cadastrado<?= $totalFilmes !== 1 ? 's' : '' ?> no sistema</p>
    </div>
 
    <div class="toolbar">
        <form method="GET" action="list.php" class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" name="busca" class="search-input" placeholder="Buscar por título, diretor ou gênero..." value="<?= htmlspecialchars($busca) ?>">
        </form>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalNovo()">
            <i class="bi bi-plus-lg me-1"></i> Cadastrar Filme
        </button>
    </div>
 
    <?php if (count($filmes) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-camera-video"></i>
        <p>Nenhum filme foi encontrado no catálogo.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($filmes as $filme): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="filme-card review-card h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex gap-3 align-items-start">
                        <img src="../poster_filmes/<?= htmlspecialchars($filme['poster_url']) ?>" alt="Poster" class="filme-poster img-fluid rounded" style="width: 90px; height: 130px; object-fit: cover;">
                        <div class="flex-grow-1">
                            <h5 class="filme-titulo mb-1" style="color: var(--text); font-weight: 600; font-size: 1.1rem;">
                                <?= htmlspecialchars($filme['titulo']) ?>
                            </h5>
                            <div class="filme-meta-item mb-1">
                                <i class="bi bi-person-video2 me-1"></i>Diretor: <?= htmlspecialchars($filme['diretor']) ?>
                            </div>
                            <div class="filme-meta-item mb-1">
                                <i class="bi bi-calendar-event me-1"></i>Ano: <?= htmlspecialchars($filme['ano_publicacao']) ?>
                            </div>
                            <span class="genero-badge badge rounded-pill mt-1" style="background-color: var(--purple-lt); font-size: 0.75rem;">
                                <?= htmlspecialchars($filme['genero']) ?>
                            </span>
                        </div>
                    </div>
                    <p class="filme-sinopse mt-3 mb-0" style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.5; text-align: justify;">
                        <?= htmlspecialchars(mb_strimwidth($filme['sinopse'], 0, 160, "...")) ?>
                    </p>
                </div>
                
                <div class="d-flex gap-2 mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.05);">
                    <button class="btn-edit flex-grow-1" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($filme)) ?>)">
                            <i class="bi bi-pencil-square me-1"></i>Editar
                    </button>
                    <button class="btn-delete" data-bs-toggle="modal" data-bs-target="#modalDelete" onclick="confirmarDelete(<?= $filme['id'] ?>, <?= htmlspecialchars(json_encode($filme['titulo'])) ?>)">
                        <i class="bi bi-trash3 me-1"></i>Excluir
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>   
    <?php endif; ?>
 
    <?php if ($totalPaginas > 1): ?>
    <nav class="d-flex justify-content-center mb-4 mt-4">
        <ul class="pagination">
            <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $paginaActual - 1 ?>&busca=<?= urlencode($busca) ?>"><i class="bi bi-chevron-left"></i></a>
            </li>
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
            <li class="page-item <?= $p === $paginaActual ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $paginaActual + 1 ?>&busca=<?= urlencode($busca) ?>"><i class="bi bi-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
 
</div>
 
<div class="modal fade modal-delete" id="modalDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center">
            <div class="modal-body py-4 px-4">
                <i class="bi bi-exclamation-triangle-fill" style="font-size: 2.4rem; color: #e74c3c;"></i>
                <h5 class="mt-3 mb-1" style="color: var(--text);">Excluir Filme?</h5>
                <p style="color: var(--text-muted); font-size: .88rem;" id="deleteNome" class="mb-4"></p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn-cancelar" data-bs-dismiss="modal">Cancelar</button>
                    <a id="linkDelete" href="#" class="btn-salvar" style="background: linear-gradient(135deg, #c0392b, #e74c3c); padding: 10px 24px; border-radius: 50px; color: #fff; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">
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
    document.getElementById('linkDelete').href = 'list.php?action=delete&id=' + id + '&busca=<?= urlencode($busca) ?>&pagina=<?= $paginaActual ?>';
}
</script>
</body>
</html>