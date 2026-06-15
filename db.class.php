<?php
// Configurações do Banco de Dados
$host    = '127.0.0.1';
$dbname  = 'trabalho';
$usuario = 'root';
$senha   = '1234';

try {
    // Cria a conexão usando PDO com suporte a caracteres UTF-8
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario, $senha);
    
    // Configura o PDO para lançar exceções em caso de erros de SQL
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    // Se a conexão falhar, exibe o erro e para a execução do script
    die("Erro crítico de conexão com o banco de dados: " . $e->getMessage());
}
?>