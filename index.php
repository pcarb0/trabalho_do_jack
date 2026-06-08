<?php
$host = "localhost";
$username = "root";
$password = "1234";
$dbname = "trabalho";
$port = "3306";



try {
    $conn = new PDO("mysql:host=127.0.0.1;dbname=trabalho;charset=utf8", "root", "1234");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "<br>";
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = $_POST["user_name"];
    $user_password = $_POST["user_password"];

    try {
    
        $stmt = $conn->prepare("SELECT senha FROM usuario WHERE username = :username");
        $stmt->execute([':username' => $user_name]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo "Senha ou usuário incorreto";
        } else {
            if ($usuario['senha'] === $user_password) {
                header("Location: home.php");
                exit; 
            } else {
                echo "Senha ou usuário incorreto";
            }
        }
    } catch (PDOException $e) {
        echo "Erro no login: " . $e->getMessage();
    }
}

?>
<div class="background">
    <div class="login_text">O paraíso dos apaixonados por cinema.</div>
    <form action="index.php" method="POST" class="login_form">
        <h1 class="title_form">Entrar na conta</h1>

        <input class="login_input_box" type="text" id="usuario" name="user_name" placeholder="Digite o nome de usuario" required>
        
        <input class="login_input_box" type="password" id="senha" name="user_password" placeholder="Digite a senha" required>
        
        <button type="submit" class="login_button">Entrar</button>

        <button type="submit" class="login_button">Criar conta</button>
    </form>
    <link rel="stylesheet " href="css/style_login.css">
</div>
