<?php
require_once 'conexao.php';

try {
    $result = $pdo->query("SELECT 1");
    echo "✅ Conexão com o banco está funcionando!";
    
    // Teste se as tabelas existem
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    echo "<br>📊 Tabelas encontradas: " . count($tables);
    foreach ($tables as $table) {
        echo "<br> - " . $table[0];
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>