<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não houver dados de login armazenados na sessão, bloqueia
if (!isset($_SESSION['usuario_id'])) {
    // Descobre dinamicamente quantos níveis voltar para chegar à raiz e encontrar o index.php
    $niveis = (basename(dirname($_SERVER['PHP_SELF'])) === 'usuarios' || basename(dirname($_SERVER['PHP_SELF'])) === 'filmes' || basename(dirname($_SERVER['PHP_SELF'])) === 'alugueis' || basename(dirname($_SERVER['PHP_SELF'])) === 'reviews') ? '../' : '';
    
    header("Location: " . $niveis . "index.php");
    exit;
}

// Função auxiliar para Administradores
function verificarAdmin() {
    if (!isset($_SESSION['usuario_admin']) || $_SESSION['usuario_admin'] !== true) {
        die("<h2 style='color:#e74c3c; text-align:center; margin-top:100px; font-family:sans-serif;'>Acesso Negado: Esta área é restrita para Administradores.</h2>");
    }
}