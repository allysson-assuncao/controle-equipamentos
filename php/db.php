<?php
// db.php
// Conexão simples usando SQLite para facilitar
try {
    // Cria o arquivo database.sqlite automaticamente se não existir
    $pdo = new PDO('sqlite:database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
