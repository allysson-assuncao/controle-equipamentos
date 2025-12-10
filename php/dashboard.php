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

// 2. Ações: (Reservar, Devolver, Criar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['reservar'])) {
        $equip_id = $_POST['equip_id'];
        $minutos = (int)$_POST['minutos'];

        $stmt = $pdo->prepare("SELECT status FROM equipamentos WHERE id = ?");
        $stmt->execute([$equip_id]);
        $equip = $stmt->fetch();

        if ($equip && $equip['status'] == 0) {
            $stmt = $pdo->prepare("UPDATE equipamentos SET status = 1, usuario_id = ?, reservado_ate = datetime('now', 'localtime', '+' || ? || ' minutes'), total_usos = total_usos + 1 WHERE id = ?");
            $stmt->execute([$user_id, $minutos, $equip_id]);
            $mensagem = "<p class='sucesso'>Reserva confirmada!</p>";
        } else {
            $mensagem = "<p class='erro'>Equipamento indisponível.</p>";
        }
    } elseif (isset($_POST['devolver'])) {
        $equip_id = $_POST['equip_id'];
        $stmt = $pdo->prepare("UPDATE equipamentos SET status = 0, usuario_id = NULL, reservado_ate = NULL WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$equip_id, $user_id]);
        if ($stmt->rowCount() > 0) $mensagem = "<p class='sucesso'>Devolvido!</p>";
    } elseif (isset($_POST['novo_equipamento'])) {
        $nome = $_POST['nome_equipamento'];
        $pdo->prepare("INSERT INTO equipamentos (nome) VALUES (?)")->execute([$nome]);
        $mensagem = "<p class='sucesso'>Adicionado!</p>";
    }
}

// 3. Consulta 1: Lista de Equipamentos
$sqlEquip = "SELECT e.*, u.nome as nome_usuario 
             FROM equipamentos e 
             LEFT JOIN usuarios u ON e.usuario_id = u.id";
$listaEquipamentos = $pdo->query($sqlEquip)->fetchAll(PDO::FETCH_ASSOC);

// 4. Consulta 2: Lista de Usuários e Contagem de Reservas Ativas (usando count na junção de equipamentos reservados pelo usuário)
$sqlUsers = "SELECT u.nome, 
             (SELECT COUNT(*) FROM equipamentos e WHERE e.usuario_id = u.id AND e.status = 1) as ativas
             FROM usuarios u
             ORDER BY ativas DESC, u.nome ASC";
$listaUsuarios = $pdo->query($sqlUsers)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel</title>
    <link rel="stylesheet" href="style.css">
    <script>
        setTimeout(function(){ window.location.reload(1); }, 30000);
    </script>
</head>
<body>
<div class="header">
    <span>Usuário: <strong><?php echo $_SESSION['user_nome']; ?></strong></span>
    <a href="logout.php" class="btn-sair">Sair</a>
</div>

<div class="container">
    <h2>Painel de Controle</h2>
    <?php echo $mensagem; ?>

    <div class="box-cadastro">
        <form method="POST">
            <input type="text" name="nome_equipamento" placeholder="Novo equipamento..." required>
            <button type="submit" name="novo_equipamento" class="btn-add">+</button>
        </form>
    </div>

    <hr>

    <h3>Equipamentos</h3>
    <div class="scroll-box">
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
            <?php foreach ($listaEquipamentos as $item):
                $sou_dono = ($item['usuario_id'] == $user_id);
                $classe_linha = $item['status'] == 1 ? ($sou_dono ? 'minha-reserva' : 'ocupado') : 'livre';
                ?>
                <tr class="<?php echo $classe_linha; ?>">
                    <td><?php echo $item['nome']; ?></td>
                    <td>
                        <?php if($item['status'] == 0): ?>
                            <span class="badge badge-livre">LIVRE</span>
                        <?php else: ?>
                            <span class="badge badge-ocupado">EM USO</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $item['nome_usuario'] ? $item['nome_usuario'] : '-'; ?></td>
                    <td><?php echo $item['reservado_ate'] ? date('H:i:s', strtotime($item['reservado_ate'])) : '-'; ?></td>
                    <td style="text-align:center;"><?php echo $item['total_usos']; ?></td>
                    <td>
                        <?php if($item['status'] == 0): ?>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="equip_id" value="<?php echo $item['id']; ?>">
                                <select name="minutos">
                                    <option value="1">1 min</option>
                                    <option value="30">30 min</option>
                                    <option value="60">1 h</option>
                                </select>
                                <button type="submit" name="reservar" class="btn-reservar">Pegar</button>
                            </form>
                        <?php elseif($sou_dono): ?>
                            <form method="POST" class="form-inline">
                                <input type="hidden" name="equip_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="devolver" class="btn-devolver">Devolver</button>
                            </form>
                        <?php else: ?>
                            <button disabled class="btn-disabled">...</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <h3>Status dos Usuários (Reservas Vigentes)</h3>
    <div class="scroll-box" style="max-height: 200px;"> <table>
            <thead>
            <tr>
                <th>Nome do Usuário</th>
                <th>Reservas Ativas Agora</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($listaUsuarios as $u): ?>
                <tr>
                    <td><?php echo $u['nome']; ?></td>
                    <td style="text-align:center; font-weight:bold; font-size:1.2em;">
                        <?php echo $u['ativas']; ?>
                    </td>
                    <td>
                        <?php if($u['ativas'] > 0): ?>
                            <span class="badge badge-ocupado">POSSUI RESERVAS</span>
                        <?php else: ?>
                            <span class="badge badge-livre">SEM PENDÊNCIAS</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</body>
</html>