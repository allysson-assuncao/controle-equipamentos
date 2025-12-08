<?php
// index.php
global $pdo;
session_start();
require 'db.php';

// Lógica de Login/Cadastro misturada no topo do arquivo (Clássico PHP Estrutural)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];

    // Verifica se usuário existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome = ?");
    $stmt->execute([$nome]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        header("Location: dashboard.php");
        exit;
    } else {
        // Cadastro automático (simplificação)
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome) VALUES (?)");
        $stmt->execute([$nome]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_nome'] = $nome;
        header("Location: dashboard.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Controle de Equipamentos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Acesso ao Sistema</h1>
        <p>Entre com seu nome. Se não existir, será criado automaticamente.</p>
        <form method="POST">
            <input type="text" name="nome" placeholder="Seu nome" required>
            <button type="submit">Entrar / Cadastrar</button>
        </form>
    </div>
</body>
</html>
