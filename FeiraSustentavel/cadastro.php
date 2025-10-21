<?php
// cadastro.php — Feira Sustentável
session_start();
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/helpers.php';

$erros = [];
$val   = ['nome'=>'', 'email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val['nome']  = trim($_POST['nome']  ?? '');
    $val['email'] = trim($_POST['email'] ?? '');
    $senha        = $_POST['senha'] ?? '';
    $confirma     = $_POST['confirmar_senha'] ?? '';

    if ($val['nome'] === '' || mb_strlen($val['nome']) < 2) {
        $erros[] = 'Informe um nome válido.';
    }
    if ($val['email'] === '' || !filter_var($val['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Informe um e-mail válido.';
    }
    if (mb_strlen($senha) < 8) {
        $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
    }
    if ($senha !== $confirma) {
        $erros[] = 'A confirmação de senha não confere.';
    }

    if (!$erros) {
        try {
            $st = $pdo->prepare('SELECT 1 FROM Usuario WHERE email = ? LIMIT 1');
            $st->execute([$val['email']]);
            if ($st->fetch()) {
                $erros[] = 'Este e-mail já está cadastrado.';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $st = $pdo->prepare('INSERT INTO Usuario (nome, email, tipo, senha) VALUES (:nome, :email, :tipo, :senha)');
                $st->execute([
                    ':nome'  => $val['nome'],
                    ':email' => $val['email'],
                    ':tipo'  => 'comum',
                    ':senha' => $hash,
                ]);

                // Mensagem personalizada com o primeiro nome
                $primeiroNome = explode(' ', $val['nome'])[0] ?? '';
                $mensagem = 'Conta criada com sucesso! Bem-vindo(a), ' . $primeiroNome . ' 👋';
                $qs = http_build_query(['registered' => 1, 'msg' => $mensagem]);
                header('Location: login.php?' . $qs);
                exit;
            }
        } catch (PDOException $e) {
            // error_log($e->getMessage());
            $erros[] = 'Erro ao criar conta. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Cadastro — Feira Sustentável</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="styles.css" />
</head>
<body>
    <main class="card" role="main">
        <h1>Cadastro</h1>
        <p class="sub">Crie sua conta para acessar a Feira Sustentável.</p>

        <?php if ($erros): ?>
            <div class="alert error" role="alert" aria-live="polite">
                <ul style="margin:0 0 0 18px;padding:0;">
                    <?php foreach ($erros as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="field">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome" value="<?= e($val['nome']) ?>" autocomplete="name" required />
            </div>

            <div class="field">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?= e($val['email']) ?>" autocomplete="email" required />
            </div>

            <div class="field">
                <label for="senha">Senha (mín. 8 caracteres)</label>
                <input type="password" id="senha" name="senha" minlength="8" autocomplete="new-password" required />
            </div>

            <div class="field">
                <label for="confirmar_senha">Confirmar senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required />
            </div>

            <button class="btn" type="submit">Criar conta</button>

            <div class="footer">
                Já tem conta? <a href="login.php">Entrar</a>
            </div>
        </form>
    </main>
</body>
</html>
