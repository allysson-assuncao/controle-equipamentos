<?php
// index.php
global $pdo;
session_start();
require 'db.php';

$erro = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $senha = $_POST['senha'];

    // 1. Verifica se usuário existe
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome = ?");
    $stmt->execute([$nome]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // --- Cenário: Login ---
        // Verifica a hash da senha
        if (password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "Usuário existe, mas a senha está incorreta.";
        }
    } else {
        // --- Cenário: Cadastro ---
        // Validação: Min 4 chars, 1 Maiúscula, 1 Minúscula
        if (preg_match('/(?=.*[a-z])(?=.*[A-Z]).{4,}/', $senha)) {

            // Cria hash segura para a senha
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, senha) VALUES (?, ?)");
                $stmt->execute([$nome, $senhaHash]);

                // Loga direto após cadastrar
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['user_nome'] = $nome;
                header("Location: dashboard.php");
                exit;
            } catch (Exception $e) {
                $erro = "Erro ao cadastrar: " . $e->getMessage();
            }
        } else {
            $erro = "Senha fraca! Use no mínimo 4 caracteres, com 1 maiúscula e 1 minúscula.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Acesso - Controle</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container login-box">
    <h1>Entrar / Cadastrar</h1>

    <?php if ($erro): ?>
        <p class="erro"><?php echo $erro; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Usuário:</label>
        <input type="text" name="nome" placeholder="Seu nome" required
               value="<?php echo isset($_POST['nome']) ? $_POST['nome'] : ''; ?>"><br>

        <label>Senha:</label>
        <input type="password" name="senha" placeholder="Min 4 chars (1 Maiús, 1 Minús)" required><br>

        <button type="submit">Acessar</button>
    </form>
    <p><small>Se o nome não existir, será criado um novo cadastro com essa senha.</small></p>
</div>
</body>
</html>