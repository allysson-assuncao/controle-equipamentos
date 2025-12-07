<?php
// dashboard.php
session_start();
require 'db.php';

// Proteção básica de rota
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$mensagem = "";

// 1. LÓGICA DE LIBERAÇÃO AUTOMÁTICA (O "Hack" do PHP tradicional)
// Toda vez que alguém carrega a página, verificamos o que expirou.
$pdo->exec("UPDATE equipamentos SET status = 0, usuario_id = NULL, reservado_ate = NULL WHERE reservado_ate < datetime('now', 'localtime') AND status = 1");

// 2. LÓGICA DE RESERVA (Processamento do Formulário)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservar'])) {
    $equip_id = $_POST['equip_id'];
    $minutos = (int)$_POST['minutos'];

    // Verifica se já não foi reservado por outro nesse milissegundo (Concorrência básica)
    $stmt = $pdo->prepare("SELECT status FROM equipamentos WHERE id = ?");
    $stmt->execute([$equip_id]);
    $equip = $stmt->fetch();

    if ($equip['status'] == 0) {
        // Calcula o tempo futuro usando SQLite modifier
        $stmt = $pdo->prepare("UPDATE equipamentos SET status = 1, usuario_id = ?, reservado_ate = datetime('now', 'localtime', '+' || ? || ' minutes') WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $minutos, $equip_id]);
        $mensagem = "<p class='sucesso'>Reserva realizada por $minutos minutos!</p>";
    } else {
        $mensagem = "<p class='erro'>Este equipamento acabou de ser reservado por outro usuário!</p>";
    }
}

// 3. LÓGICA DE CADASTRO DE EQUIPAMENTO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['novo_equipamento'])) {
    $nome_equip = $_POST['nome_equipamento'];
    $stmt = $pdo->prepare("INSERT INTO equipamentos (nome) VALUES (?)");
    $stmt->execute([$nome_equip]);
    $mensagem = "<p class='sucesso'>Equipamento cadastrado!</p>";
}

// 4. CONSULTA PARA EXIBIÇÃO (Carrega tudo de novo)
$sql = "SELECT e.*, u.nome as nome_usuario
        FROM equipamentos e
        LEFT JOIN usuarios u ON e.usuario_id = u.id";
$lista = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel - Controle</title>
    <link rel="stylesheet" href="style.css">
    <script>
        // Refresh automático a cada 30 segundos para ver se liberou algo
        setTimeout(function(){
           window.location.reload(1);
        }, 30000);
    </script>
</head>
<body>
    <div class="header">
        <span>Bem-vindo, <strong><?php echo $_SESSION['user_nome']; ?></strong></span>
        <a href="logout.php" class="btn-sair">Sair</a>
    </div>

    <div class="container">
        <h2>Controle de Equipamentos</h2>
        <?php echo $mensagem; ?>

        <div class="box-cadastro">
            <h3>Cadastrar Novo Equipamento</h3>
            <form method="POST">
                <input type="text" name="nome_equipamento" placeholder="Nome do equipamento" required>
                <button type="submit" name="novo_equipamento">Adicionar</button>
            </form>
        </div>

        <hr>

        <h3>Lista de Equipamentos</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Equipamento</th>
                    <th>Status</th>
                    <th>Reservado Por</th>
                    <th>Libera em</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista as $item): ?>
                <tr class="<?php echo $item['status'] == 1 ? 'ocupado' : 'livre'; ?>">
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo $item['nome']; ?></td>

                    <td>
                        <?php if($item['status'] == 0): ?>
                            <span class="badge-livre">LIVRE</span>
                        <?php else: ?>
                            <span class="badge-ocupado">EM USO</span>
                        <?php endif; ?>
                    </td>

                    <td><?php echo $item['nome_usuario'] ? $item['nome_usuario'] : '-'; ?></td>

                    <td><?php echo $item['reservado_ate'] ? date('H:i:s', strtotime($item['reservado_ate'])) : '-'; ?></td>

                    <td>
                        <?php if($item['status'] == 0): ?>
                            <form method="POST" style="display:inline-flex;">
                                <input type="hidden" name="equip_id" value="<?php echo $item['id']; ?>">
                                <select name="minutos">
                                    <option value="1">1 min (Teste)</option>
                                    <option value="30">30 min</option>
                                    <option value="60">1 hora</option>
                                </select>
                                <button type="submit" name="reservar">Reservar</button>
                            </form>
                        <?php else: ?>
                            <button disabled>Indisponível</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
