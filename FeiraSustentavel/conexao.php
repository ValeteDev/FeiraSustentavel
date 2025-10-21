<?php
// conexao.php — centraliza a conexão PDO
// Ajuste as credenciais abaixo para seu ambiente.

$dsn  = getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=sua_base;charset=utf8mb4';
$user = getenv('DB_USER') ?: 'seu_usuario';
$pass = getenv('DB_PASS') ?: 'sua_senha';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    die('Erro de conexão com o banco de dados.');
}
