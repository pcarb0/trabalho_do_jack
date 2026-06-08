<div class="background">
    <?php include("header.php"); ?>
    <div class="login_text">O paraíso dos apaixonados por cinema.</div>
    <form action="login.php" method="POST" class="login_form">
        <h1 class="title_form">Entrar na conta</h1>

        <input class="login_input_box" type="text" id="usuario" name="user_name" placeholder="Digite o nome de usuario" required>
        
        <input class="login_input_box" type="password" id="senha" name="user_password" placeholder="Digite a senha" required>
        
        <button type="submit" class="login_button">Entrar</button>

        <button type="submit" class="login_button">Criar conta</button>
    </form>
    <link rel="stylesheet " href="css/style_login.css">
</div>