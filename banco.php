<?php
$hostname = "localhost";
$username = "root";
$password = "";
$database = "trabalho";
$port = "3306";

$conn = new PDO("mysql:host=$hostname;dbname=$database;port=$port,charset=utf8", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


if($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_name = $_POST["user_name"];
    $user_password = $_POST["user_password"];

    $sql = "SELECT senha FROM usuarios WHERE username = '$user_name'";
    $result = $conn->query($sql);
    $result2 = $resultfetchAll(PDO::FETCH_ASSOC);
    echo $result2;
    if ($result ->num_rows === 0) {
        echo "senha ou usuario incorreto";
    } else {
        if ($result2['password'] === $user_password) {
            header("Location: home.php");
        } else {
            echo "senha ou usuario incorreto";
        }
    }
}
?>

<form action="autenticacao.php" method="POST">
    <label for="usuario">Usuario:</label>
    <input type="text" id="usuario" name="user_name" required>
    
    <label for="senha">Senha:</label>
    <input type="password" id="senha" name="user_password" required>
    
    <button type="submit">Entrar</button>
</form>