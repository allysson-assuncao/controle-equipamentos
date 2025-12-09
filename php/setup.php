<?php
// setup.php
global $pdo;
require 'db.php';

try {
    // DROP TABLE força o reset para garantir a nova estrutura
    $pdo->exec("DROP TABLE IF EXISTS equipamentos");
    $pdo->exec("DROP TABLE IF EXISTS usuarios");

    // Tabela de Usuários com SENHA
    $pdo->exec("CREATE TABLE usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL UNIQUE,
        senha TEXT NOT NULL
    )");

    // Tabela de Equipamentos com TOTAL_USOS
    $pdo->exec("CREATE TABLE equipamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        status INTEGER DEFAULT 0,
        usuario_id INTEGER,
        reservado_ate DATETIME,
        total_usos INTEGER DEFAULT 0,
        FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
    )");

    // Dados iniciais
    $pdo->exec("INSERT INTO equipamentos (nome) VALUES ('Projetor Epson'), ('Notebook Dell'), ('Caixa de Som JBL'), ('Tablet Gráfico')");

    echo "<h3>Banco de dados atualizado!</h3>";
    echo "<a href='index.php'>Ir para Login</a>";

} catch (Exception $e) {
    echo "Erro ao configurar banco: " . $e->getMessage();
}
?>