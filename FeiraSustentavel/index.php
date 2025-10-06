<?php
// index.php — Landing + modais que disparam os cadastros (com imagens)
declare(strict_types=1);
session_start();

// Gera token CSRF p/ os formulários
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf'];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Feira Sustentável</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <meta name="csrf-token" content="<?= htmlspecialchars($CSRF, ENT_QUOTES, 'UTF-8') ?>">
    
    <link rel="stylesheet" href="style.css">
    
    
</head>
<body>
    <header class="cabecalho">
        <div class="logo-menu">
            <h1>Feira Sustentável</h1>
        </div>
    </header>

    <main class="conteudo-principal">
        <section class="frase-efeito">
            <h2>Conectamos feirantes e famílias para reduzir o desperdício de alimentos</h2>
            <p>Escolha se deseja cadastrar uma família ou registrar uma doação</p>
        </section>

        <section class="opcoes-cadastro">
            <div class="cadastro-familias">
                <div class="box-img">
                    <!-- IMAGEM CORRETA -->
                    <img src="familia.png" alt="Ícone família" width="100" height="100">
                </div>
                <h3>Famílias</h3>
                <p>Cadastre famílias para receber alimentos</p>
                <button id="btn-cadastrar-familia">Cadastrar famílias</button>
            </div>

            <div class="cadastro-doacoes">
                <div class="box-img">
                    <!-- IMAGEM CORRETA -->
                    <img src="lealdade.png" alt="Ícone doação" width="100" height="100">
                </div>
                <h3>Doações</h3>
                <p>Registre os alimentos disponíveis para doação</p>
                <button id="btn-registrar-doacao">Registrar Doação</button>
            </div>
        </section>

        <!-- ADICIONE ESTA SEÇÃO PARA GERENCIAMENTO -->
        <section class="gerenciamento-links" style="margin-top: 3rem; text-align: center;">
            <h3 style="margin-bottom: 1rem; color: #2E7D32;">Gerenciar Dados Cadastrados</h3>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="familias.php?action=list" style="background: #2196F3; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                    Ver Famílias Cadastradas
                </a>
                <a href="doacoes.php?action=list" style="background: #FF9800; color: white; padding: 12px 24px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                    Ver Doações Registradas
                </a>
            </div>
        </section>
    </main>

    <!-- Modal -->
    <div class="modal-container" id="modal-container" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <button class="fechar-modal" id="fechar-modal" aria-label="Fechar">×</button>
            <div id="conteudo-modal"></div>
        </div>
    </div>

    <!-- JS NO FINAL DO BODY -->
    <script src="main.js"></script>
</body>
</html>