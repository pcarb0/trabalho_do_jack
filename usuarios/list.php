<?php
require_once '../auth.php'; // Verifica se está logado
verificarAdmin();
// ─── Conexão com o banco de dados centralizado ────────────────────────────────
require_once '../db.class.php';
 
$mensagem = "";
$tipoMensagem = "";
 
// ─── CRUD: Deletar Usuário ────────────────────────────────────────────────___
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmtRev = $conn->prepare("DELETE FROM reviews WHERE usuario_id = :id");
        $stmtRev->execute([':id' => $id]);
        
        $stmtAlug = $conn->prepare("DELETE FROM alugueis WHERE usuario_id = :id");
        $stmtAlug->execute([':id' => $id]);

        $stmt = $conn->prepare("DELETE FROM usuario WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        $mensagem = "Usuário excluído com sucesso!";
        $tipoMensagem = "success";
    } catch (PDOException $e) {
        $mensagem = "Erro ao excluir usuário: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}
 
// ─── CRUD: Criar / Editar Usuário ────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome          = trim($_POST['nome'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $numero        = trim($_POST['numero'] ?? '');
    $senha         = $_POST['senha'] ?? '';
    $administrador = isset($_POST['administrador']) ? 1 : 0;
    $id_edicao     = (int)($_POST['id_edicao'] ?? 0);
 
    // Validação corrigida: Apenas o username é estritamente obrigatório aqui
    if ($username === '') {
        $mensagem = "O nome de usuário (username) é obrigatório.";
        $tipoMensagem = "warning";
    } elseif ($id_edicao === 0 && empty($senha)) {
        $mensagem = "A senha é obrigatória para novos cadastros.";
        $tipoMensagem = "warning";
    } else {
        try {
            // ── VERIFICAÇÃO DE DISPONIBILIDADE DO USERNAME ───────────────────
            if ($id_edicao > 0) {
                // Na edição: verifica se já existe o username em OUTRO id diferente do atual
                $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM usuario WHERE username = :user AND id != :id");
                $stmtCheck->execute([':user' => $username, ':id' => $id_edicao]);
            } else {
                // No cadastro: verifica de forma global
                $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM usuario WHERE username = :user");
                $stmtCheck->execute([':user' => $username]);
            }
            
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $mensagem = "Erro: O nome de usuário '@$username' já está sendo utilizado!";
                $tipoMensagem = "danger";
            } else {
                // Se passou no teste de duplicidade, salva os dados
                if ($id_edicao > 0) {
                    // Modo Edição
                    if (!empty($senha)) {
                        $stmt = $conn->prepare(
                            "UPDATE usuario 
                             SET nome = :nome, username = :username, email = :email, numero = :numero, senha = :senha, adminstrador = :admin 
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':nome'     => !empty($nome) ? $nome : null,
                            ':username' => $username,
                            ':email'    => !empty($email) ? $email : null,
                            ':numero'   => !empty($numero) ? $numero : null,
                            ':senha'    => $senha,
                            ':admin'    => $administrador,
                            ':id'       => $id_edicao
                        ]);
                    } else {
                        $stmt = $conn->prepare(
                            "UPDATE usuario 
                             SET nome = :nome, username = :username, email = :email, numero = :numero, adminstrador = :admin 
                             WHERE id = :id"
                        );
                        $stmt->execute([
                            ':nome'     => !empty($nome) ? $nome : null,
                            ':username' => $username,
                            ':email'    => !empty($email) ? $email : null,
                            ':numero'   => !empty($numero) ? $numero : null,
                            ':admin'    => $administrador,
                            ':id'       => $id_edicao
                        ]);
                    }
                    $mensagem = "Dados do usuário atualizados com sucesso!";
                } else {
                    // Modo Cadastro
                    $stmt = $conn->prepare(
                        "INSERT INTO usuario (nome, username, email, numero, senha, adminstrador) 
                         VALUES (:nome, :username, :email, :numero, :senha, :admin)"
                    );
                    $stmt->execute([
                        ':nome'     => !empty($nome) ? $nome : null,
                        ':username' => $username,
                        ':email'    => !empty($email) ? $email : null,
                        ':numero'   => !empty($numero) ? $numero : null,
                        ':senha'    => $senha,
                        ':admin'    => $administrador
                    ]);
                    $mensagem = "Usuário cadastrado com sucesso!";
                }
                $tipoMensagem = "success";
            }
        } catch (PDOException $e) {
            $mensagem = "Erro no sistema ao processar dados: " . $e->getMessage();
            $tipoMensagem = "danger";
        }
    }
}
 
// ─── Configurações de Paginação e Filtros ─────────────────────────────────────
$porPagina = 6; 
$paginaActual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($paginaActual - 1) * $porPagina;
 
$busca = trim($_GET['busca'] ?? '');
$paramsBusca = [];
$whereBusca = "";
 
if ($busca !== '') {
    $whereBusca = "WHERE nome LIKE :busca OR username LIKE :busca2 OR email LIKE :busca3";
    $paramsBusca = [
        ':busca'  => "%$busca%",
        ':busca2' => "%$busca%",
        ':busca3' => "%$busca%"
    ];
}
 
$stmtTotal = $conn->prepare("SELECT COUNT(*) FROM usuario $whereBusca");
$stmtTotal->execute($paramsBusca);
$totalUsuarios = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalUsuarios / $porPagina));
 
$stmtUsuarios = $conn->prepare("SELECT * FROM usuario $whereBusca ORDER BY id DESC LIMIT :limite OFFSET :offset");
foreach ($paramsBusca as $chave => $valor) {
    $stmtUsuarios->bindValue($chave, $valor);
}
$stmtUsuarios->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmtUsuarios->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtUsuarios->execute();
$usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locadora — Gerenciar Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=Nunito:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style_home.css">
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
        <h1>Controle de Usuários</h1>
        <p><?= $totalUsuarios ?> usuário<?= $totalUsuarios !== 1 ? 's' : '' ?> cadastrado<?= $totalUsuarios !== 1 ? 's' : '' ?> na plataforma</p>
    </div>
 
    <div class="toolbar">
        <form method="GET" action="list.php" class="search-wrapper">
            <i class="bi bi-search"></i>
            <input type="text" name="busca" class="search-input" placeholder="Buscar por nome, username ou e-mail..." value="<?= htmlspecialchars($busca) ?>">
        </form>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalNovo()">
            <i class="bi bi-plus-lg me-1"></i> Criar Usuário
        </button>
    </div>
 
    <?php if (count($usuarios) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-people"></i>
        <p>Nenhum usuário foi localizado.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($usuarios as $u): ?>
        <div class="col-12 col-md-6 col-xl-4">
            <div class="filme-card h-100 d-flex flex-column justify-content-between" style="padding: 20px;">
                <div>
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle" style="font-size: 1.8rem; color: var(--purple);"></i>
                            <div>
                                <h5 class="mb-0" style="color: var(--text); font-weight: 600; font-size: 1.05rem;">
                                    <?= htmlspecialchars(!empty($u['nome']) ? $u['nome'] : $u['username']) ?>
                                </h5>
                                <span style="font-size: 0.82rem; color: var(--purple-lt);">@<?= htmlspecialchars($u['username'] ?? '') ?></span>
                            </div>
                        </div>
                        <?php if (isset($u['adminstrador']) && $u['adminstrador']): ?>
                            <span class="status-badge status-devolvido" style="background: rgba(130, 49, 211, 0.15); color: #af73ff; border: 1px solid rgba(130, 49, 211, 0.3);"><i class="bi bi-shield-lock-fill"></i> Admin</span>
                        <?php else: ?>
                            <span class="status-badge status-ativo"><i class="bi bi-person"></i> Cliente</span>
                        <?php endif; ?>
                    </div>
 
                    <div class="mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.03);">
                        <?php if (!empty($u['email'])): ?>
                        <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($u['email']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($u['numero'])): ?>
                        <div class="mt-1" style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-telephone"></i> <?= htmlspecialchars($u['numero']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-3 pt-2" style="border-top: 1px solid rgba(255,255,255,0.05);">
                    <button class="btn-edit flex-grow-1" data-bs-toggle="modal" data-bs-target="#modalCrud" onclick="abrirModalEditar(<?= htmlspecialchars(json_encode($u)) ?>)">
                        <i class="bi bi-pencil-square me-1"></i>Editar
                    </button>
                    <button class="btn-delete" data-bs-toggle="modal" data-bs-target="#modalDelete" onclick="confirmarDelete(<?= $u['id'] ?>, <?= htmlspecialchars(json_encode(!empty($u['nome']) ? $u['nome'] : $u['username'])) ?>)">
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
                <h5 class="mt-3 mb-1" style="color: var(--text);">Excluir Usuário?</h5>
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
function confirmarDelete(id, nome) {
    document.getElementById('deleteNome').textContent = '"' + nome + '"';
    document.getElementById('linkDelete').href = 'list.php?action=delete&id=' + id + '&busca=<?= urlencode($busca) ?>&pagina=<?= $paginaActual ?>';
}
</script>
</body>
</html>