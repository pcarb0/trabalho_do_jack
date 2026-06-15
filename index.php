<?php
// Inicializa a sessão antes de qualquer HTML/Saída
session_start();

require_once 'db.class.php';
 
$mensagem = "";
$tipoMensagem = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = trim($_POST["user_name"]);
    $user_password = $_POST["user_password"];

    try {
        // Buscamos o ID e se é ADMIN além da senha
        $stmt = $conn->prepare("SELECT id, senha, adminstrador FROM usuario WHERE username = :username");
        $stmt->execute([':username' => $user_name]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $mensagem = "Senha ou usuário incorreto";
            $tipoMensagem = "danger";
        } else {
            if ($usuario['senha'] === $user_password) {
                // SALVA OS DADOS NA SESSÃO DO NAVEGADOR
                $_SESSION['usuario_id']    = $usuario['id'];
                $_SESSION['usuario_nome']  = $user_name;
                $_SESSION['usuario_admin'] = ((int)$usuario['adminstrador'] === 1);

                header("Location: filmes/list.php");
                exit; 
            } else {
                $mensagem = "Senha ou usuário incorreto";
                $tipoMensagem = "danger";
            }
        }
    } catch (PDOException $e) {
        $mensagem = "Erro no login: " . $e->getMessage();
        $tipoMensagem = "danger";
    }
}
?>
<div class="background">
    <div class="login_text">O paraíso dos apaixonados por cinema.</div>
    <form action="index.php" method="POST" class="login_form">
        <h1 class="title_form">Entrar na conta</h1>

        <?php if (!empty($mensagem)): ?>
            <div style="color: #e74c3c; text-align: center; margin-bottom: 15px; font-size: 0.9rem; font-weight: bold;">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <input class="login_input_box" type="text" id="usuario" name="user_name" placeholder="Digite o nome de usuario" required autocomplete="off">
        
        <input class="login_input_box" type="password" id="senha" name="user_password" placeholder="Digite a senha" required>
        
        <button type="submit" class="login_button">Entrar</button>

        <button type="button" class="login_button" style="background: transparent; border: 1px solid rgba(255,255,255,0.2); margin-top: 5px;">Criar conta</button>
    </form>
    <link rel="stylesheet" href="css/style_login.css">
</div>