<?php
// setup.php
require 'db.php';

try {
    // Tabela de Usuários
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL
    )");

    // Tabela de Equipamentos
    // status: 0 = livre, 1 = reservado
    // reservado_ate: timestamp do fim da reserva
    $pdo->exec("CREATE TABLE IF NOT EXISTS equipamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        status INTEGER DEFAULT 0,
        usuario_id INTEGER,
        reservado_ate DATETIME,
        FOREIGN KEY(usuario_id) REFERENCES usuarios(id)
    )");

    // Inserir alguns dados de exemplo se estiver vazio
    $res = $pdo->query("SELECT COUNT(*) FROM equipamentos");
    if ($res->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO equipamentos (nome) VALUES ('Projetor Epson'), ('Notebook Dell'), ('Caixa de Som JBL')");
    }

    echo "Banco de dados e tabelas criados com sucesso! <a href='index.php'>Ir para Home</a>";

} catch (Exception $e) {
    echo "Erro ao configurar banco: " . $e->getMessage();
}
?>
