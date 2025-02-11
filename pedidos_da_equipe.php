<?php
session_start();

$host = "localhost";
$dbname = "usuarios01";
$username = "admin";
$password = "admin";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se o usuário é admin
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
        header("Location: index.php");
        exit();
    }

    // Processa ações de aceitar ou rejeitar
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action = $_GET['action'];
        $pedido_id = intval($_GET['id']);

        // Busca os dados do pedido para validar e obter informações
        $stmt = $pdo->prepare("SELECT * FROM pedidos_revisao WHERE id = :id");
        $stmt->execute([':id' => $pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido && $pedido['status'] == 'Pendente') {
            if ($action == 'aceitar') {
                // Atualiza o status para Aceito
                $stmt = $pdo->prepare("UPDATE pedidos_revisao SET status = 'Aceito' WHERE id = :id");
                $stmt->execute([':id' => $pedido_id]);
                // Atualiza o registro na tabela pontos com a nova data/hora
                $stmt = $pdo->prepare("UPDATE pontos SET data_hora = :nova_data_hora WHERE id = :ponto_id");
                $stmt->execute([
                    ':nova_data_hora' => $pedido['nova_data_hora'],
                    ':ponto_id'       => $pedido['ponto_id']
                ]);
            } elseif ($action == 'rejeitar') {
                // Atualiza o status para Rejeitado
                $stmt = $pdo->prepare("UPDATE pedidos_revisao SET status = 'Rejeitado' WHERE id = :id");
                $stmt->execute([':id' => $pedido_id]);
            }
            // Redireciona para evitar reenvio do formulário
            header("Location: pedidos_da_equipe.php");
            exit();
        }
    }

    // Busca todos os pedidos de revisão
    $stmt = $pdo->query("SELECT * FROM pedidos_revisao ORDER BY data_pedido DESC");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos da Equipe</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table { 
      width: 100%; 
      border-collapse: collapse; 
    }
    th, td { 
      padding: 8px; 
      border: 1px solid #ddd; 
      text-align: center; 
    }
    th { background-color:rgb(221, 103, 103); }
  </style>
</head>
<body>
  <div class="container">
    <h2>Pedidos de Revisão da Equipe</h2>
    <p>Administrador: <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></p>
    
    <?php if(count($pedidos) > 0): ?>
      <table>
        <tr>
          <th>ID do Pedido</th>
          <th>ID do Ponto</th>
          <th>Usuário</th>
          <th>Data/Hora Atual</th>
          <th>Nova Data/Hora</th>
          <th>Justificativa</th>
          <th>Status</th>
          <th>Data do Pedido</th>
          <th>Ação</th>
        </tr>
        <?php foreach($pedidos as $pedido): ?>
          <tr>
            <td><?= htmlspecialchars($pedido['id']) ?></td>
            <td><?= htmlspecialchars($pedido['ponto_id']) ?></td>
            <td><?= htmlspecialchars($pedido['usuario']) ?></td>
            <td><?= date("d/m/Y - H:i:s", strtotime($pedido['data_hora_atual'])) ?></td>
            <td><?= date("d/m/Y - H:i:s", strtotime($pedido['nova_data_hora'])) ?></td>
            <td><?= nl2br(htmlspecialchars($pedido['justificativa'])) ?></td>
            <td><?= htmlspecialchars($pedido['status']) ?></td>
            <td><?= date("d/m/Y - H:i:s", strtotime($pedido['data_pedido'])) ?></td>
            <td>
              <?php if($pedido['status'] == 'Pendente'): ?>
                <a href="pedidos_da_equipe.php?action=aceitar&id=<?= $pedido['id'] ?>" onclick="return confirm('Aceitar este pedido?')">Aceitar</a>
                <br>
                <a href="pedidos_da_equipe.php?action=rejeitar&id=<?= $pedido['id'] ?>" onclick="return confirm('Rejeitar este pedido?')">Rejeitar</a>
              <?php else: ?>
                -
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php else: ?>
      <p>Nenhum pedido de revisão encontrado.</p>
    <?php endif; ?>
    
    <br>
    <a href="index.php">Voltar</a>
  </div>
</body>
</html>
