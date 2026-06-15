<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o ficheiro que chamou o header está na raiz ou dentro de uma pasta (filmes, usuarios, etc)
$pasta_atual = basename(dirname($_SERVER['PHP_SELF']));
$prefixo = ($pasta_atual === 'filmes' || $pasta_atual === 'reviews' || $pasta_atual === 'alugueis' || $pasta_atual === 'usuarios') ? '../' : '';
?>
<nav class="navbar">
    <div class="logo">
        <a href="#">
           <img src="<?= $prefixo ?>img/logo.png" alt="Logo do Meu Site">
        </a>
    </div>
    
    <ul class="nav-links">
      <li><a href="<?= $prefixo ?>filmes/list.php">Filmes</a></li>
      <li><a href="<?= $prefixo ?>reviews/reviews.php">Minhas Reviews</a></li>
      <li><a href="<?= $prefixo ?>alugueis/list.php">Meus alugueis</a></li>
      <li><a href="<?= $prefixo ?>usuarios/list.php">Usuarios</a></li>
    </ul>
    
    <div class="d-flex align-items-center gap-3">
    <?php if(isset($_SESSION['usuario_nome'])): ?>
        <span class="text-muted small" style="color: var(--text-muted);"><i class="bi bi-person me-1"></i><?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
    <?php endif; ?>

    <a href="<?= $prefixo ?>sair.php" class="nav-link nav-logout d-flex align-items-center gap-1" style="text-decoration: none; color: #e74c3c; font-weight: 600;">
        <i class="bi bi-box-arrow-right"></i> Sair
    </a>
    </div>
</nav>
<link rel="stylesheet" href="<?= $prefixo ?>css/style_header.css">
