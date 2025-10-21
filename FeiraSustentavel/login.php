<?php
// login.php — Feira Sustentável (atualizado com mensagem personalizada)
session_start();

// Inclui conexão e helpers (ajuste caminho se necessário)
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/helpers.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Informe e-mail e senha.';
    } else {
        $st = $pdo->prepare('SELECT id, nome, tipo, senha FROM Usuario WHERE email = ?');
        $st->execute([$email]);
        $usuario = $st->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario'] = [
                'id'   => $usuario['id'],
                'nome' => $usuario['nome'],
                'tipo' => $usuario['tipo'],
            ];
            header('Location: index.php');
            exit;
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    }
}

$registered = !empty($_GET['registered']);
$customMsg  = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Login — Feira Sustentável</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <main class="card" role="main">
        <h1>Login</h1>
        <p class="sub">Acesse sua conta para continuar.</p>

        <?php if ($registered): ?>
            <div class="alert success" role="status" aria-live="polite">
                <?= e($customMsg !== '' ? $customMsg : 'Conta criada com sucesso! Faça login para continuar.') ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alert error" role="alert" aria-live="polite"><?= e($erro) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" autocomplete="email" required />
            </div>

            <div class="field">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" autocomplete="current-password" required />
            </div>

            <button class="btn" type="submit">Entrar</button>

            <div class="footer">
                Não tem conta? <a href="cadastro.php">Criar conta</a>
            </div>
        </form>
    </main>
</body>
</html>
