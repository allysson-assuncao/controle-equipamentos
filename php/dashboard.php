<?php
global $pdo;
session_start();
require 'db.php';

// Proteção de rota
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$mensagem = "";
$user_id = $_SESSION['user_id'];

// 1. Auto-Liberação dos equipamentos (Lazy Load)
$pdo->exec("UPDATE equipamentos SET status = 0, usuario_id = NULL, reservado_ate = NULL WHERE reservado_ate < datetime('now', 'localtime') AND status = 1");

// 2. Ação: Reservar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reservar'])) {
    $equip_id = $_POST['equip_id'];
    $minutos = (int)$_POST['minutos'];

    // Verifica disponibilidade
    $stmt = $pdo->prepare("SELECT status FROM equipamentos WHERE id = ?");
    $stmt->execute([$equip_id]);
    $equip = $stmt->fetch();

    if ($equip && $equip['status'] == 0) {
        // Atualiza status, define timer e incrementa contador de uso
        $stmt = $pdo->prepare("UPDATE equipamentos SET status = 1, usuario_id = ?, reservado_ate = datetime('now', 'localtime', '+' || ? || ' minutes'), total_usos = total_usos + 1 WHERE id = ?");
        $stmt->execute([$user_id, $minutos, $equip_id]);
        $mensagem = "<p class='sucesso'>Reserva confirmada!</p>";
    }
}

// 3. Ação: Devolver (Liberar antecipadamente)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['devolver'])) {
    $equip_id = $_POST['equip_id'];

    // Só libera se o equipamento estiver com o usuário que reservou logado
    $stmt = $pdo->prepare("UPDATE equipamentos SET status = 0, usuario_id = NULL, reservado_ate = NULL WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$equip_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $mensagem = "<p class='sucesso'>Equipamento devolvido com sucesso!</p>";
    } else {
        $mensagem = "<p class='erro'>Você não pode liberar um equipamento que não reservou.</p>";
    }
}

// 4. Ação: Novo equipamento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['novo_equipamento'])) {
    $nome = $_POST['nome_equipamento'];
    $pdo->prepare("INSERT INTO equipamentos (nome) VALUES (?)")->execute([$nome]);
    $mensagem = "<p class='sucesso'>Adicionado!</p>";
}

// 5. Listagem dos equipamentos
$sql = "SELECT e.*, u.nome as nome_usuario 
        FROM equipamentos e 
        LEFT JOIN usuarios u ON e.usuario_id = u.id";
$lista = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel</title>
    <link rel="stylesheet" href="style.css">
    <script>
        // Refresh a cada 30s
        setTimeout(function () {
            window.location.reload(1);
        }, 30000);
    </script>
</head>
<body>
<div class="header">
    <span>Usuário: <strong><?php echo $_SESSION['user_nome']; ?></strong></span>
    <a href="logout.php" class="btn-sair">Sair</a>
</div>

<div class="container">
    <h2>Controle de Equipamentos</h2>
    <?php echo $mensagem; ?>

    <div class="box-cadastro">
        <form method="POST">
            <input type="text" name="nome_equipamento" placeholder="Novo equipamento..." required>
            <button type="submit" name="novo_equipamento" class="btn-add">+</button>
        </form>
    </div>

    <table>
        <thead>
        <tr>
            <th>Equipamento</th>
            <th>Status</th>
            <th>Reservado Por</th>
            <th>Fim da Reserva</th>
            <th>Total Usos</th>
            <th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($lista as $item):
            // Verifica se é o dono da reserva atual
            $sou_dono = ($item['usuario_id'] == $user_id);
            $classe_linha = $item['status'] == 1 ? ($sou_dono ? 'minha-reserva' : 'ocupado') : 'livre';
            ?>
            <tr class="<?php echo $classe_linha; ?>">
                <td><?php echo $item['nome']; ?></td>

                <td>
                    <?php if ($item['status'] == 0): ?>
                        <span class="badge badge-livre">LIVRE</span>
                    <?php else: ?>
                        <span class="badge badge-ocupado">OCUPADO</span>
                    <?php endif; ?>
                </td>

                <td><?php echo $item['nome_usuario'] ? $item['nome_usuario'] : '-'; ?></td>
                <td><?php echo $item['reservado_ate'] ? date('H:i:s', strtotime($item['reservado_ate'])) : '-'; ?></td>
                <td style="text-align:center;"><?php echo $item['total_usos']; ?></td>

                <td>
                    <?php if ($item['status'] == 0): ?>
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="equip_id" value="<?php echo $item['id']; ?>">
                            <select name="minutos">
                                <option value="1">1 min</option>
                                <option value="30">30 min</option>
                                <option value="60">1 hora</option>
                            </select>
                            <button type="submit" name="reservar" class="btn-reservar">Reservar</button>
                        </form>

                    <?php elseif ($sou_dono): ?>
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="equip_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="devolver" class="btn-devolver">Liberar Agora</button>
                        </form>

                    <?php else: ?>
                        <button disabled class="btn-disabled">Indisponível</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>