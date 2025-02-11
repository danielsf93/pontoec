<?php
session_start();

$host = "localhost";
$dbname = "usuarios01";
$username = "admin";
$password = "admin";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['usuario'])) {
        header("Location: index.php");
        exit();
    }
    
    $usuario = $_SESSION['usuario'];
    $mensagem = '';

    // Se o formulário de pedido de revisão foi enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ponto_id'], $_POST['nova_data_hora'], $_POST['justificativa'])) {
        $ponto_id = $_POST['ponto_id'];
        $nova_data_hora = $_POST['nova_data_hora'];
        $justificativa = $_POST['justificativa'];

        // Busca o ponto do usuário para garantir que ele existe
        $stmt = $pdo->prepare("SELECT data_hora FROM pontos WHERE id = :id AND usuario = :usuario");
        $stmt->execute([':id' => $ponto_id, ':usuario' => $usuario]);
        $ponto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ponto) {
            $mensagem = "Ponto não encontrado.";
        } else {
            $data_hora_atual = $ponto['data_hora'];
            // Insere o pedido de revisão na nova tabela
            $stmt = $pdo->prepare("INSERT INTO pedidos_revisao (ponto_id, usuario, data_hora_atual, nova_data_hora, justificativa) 
                                   VALUES (:ponto_id, :usuario, :data_hora_atual, :nova_data_hora, :justificativa)");
            $stmt->execute([
                ':ponto_id'       => $ponto_id,
                ':usuario'        => $usuario,
                ':data_hora_atual'=> $data_hora_atual,
                ':nova_data_hora' => $nova_data_hora,
                ':justificativa'  => $justificativa
            ]);
            $mensagem = "Pedido de revisão enviado com sucesso.";
        }
    }
    
    // Se o usuário clicou no link para pedir revisão, será passado um GET com o id do ponto
    if (isset($_GET['ponto_id'])) {
        $ponto_id = $_GET['ponto_id'];
        $stmt = $pdo->prepare("SELECT id, data_hora, tipo FROM pontos WHERE id = :id AND usuario = :usuario");
        $stmt->execute([':id' => $ponto_id, ':usuario' => $usuario]);
        $pontoParaRevisao = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Buscar todos os pontos do usuário
    $stmt = $pdo->prepare("SELECT id, data_hora, tipo FROM pontos WHERE usuario = :usuario ORDER BY data_hora DESC");
    $stmt->execute([':usuario' => $usuario]);
    $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedir Revisão</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h2>Pedidos de Revisão - Meus Pontos</h2>
    <p>Usuário: <strong><?= htmlspecialchars($usuario) ?></strong></p>
    
    <?php if (!empty($mensagem)): ?>
      <p><?= htmlspecialchars($mensagem) ?></p>
    <?php endif; ?>
    
    <?php if (isset($pontoParaRevisao)): ?>
      <h3>Pedir Revisão para o Ponto</h3>
      <p>
        <strong>Data e Hora Atual:</strong> <?= date("d/m/Y - H:i:s", strtotime($pontoParaRevisao['data_hora'])) ?> 
        (Tipo: <?= htmlspecialchars($pontoParaRevisao['tipo']) ?>)
      </p>
      <form method="POST">
         <input type="hidden" name="ponto_id" value="<?= htmlspecialchars($pontoParaRevisao['id']) ?>">
         <label for="nova_data_hora">Nova Data e Hora (Formato: YYYY-MM-DD HH:MM:SS):</label>
         <input type="text" name="nova_data_hora" id="nova_data_hora" placeholder="ex: 2025-02-11 09:30:00" required>
         <br>
         <label for="justificativa">Justificativa:</label>
         <textarea name="justificativa" id="justificativa" required></textarea>
         <br>
         <button type="submit">Enviar Pedido de Revisão</button>
      </form>
      <br>
      <a href="pedir_revisao.php">Cancelar</a>
    <?php else: ?>
      <h3>Meus Pontos</h3>
      <?php if(count($pontos) > 0): ?>
        <table border="1">
          <tr>
            <th>ID</th>
            <th>Data e Hora</th>
            <th>Tipo</th>
            <th>Ação</th>
          </tr>
          <?php foreach($pontos as $ponto): ?>
            <tr>
              <td><?= htmlspecialchars($ponto['id']) ?></td>
              <td><?= date("d/m/Y - H:i:s", strtotime($ponto['data_hora'])) ?></td>
              <td><?= htmlspecialchars($ponto['tipo']) ?></td>
              <td>
                <a href="pedir_revisao.php?ponto_id=<?= htmlspecialchars($ponto['id']) ?>">Pedir Revisão</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <p>Nenhum registro de ponto encontrado.</p>
      <?php endif; ?>
    <?php endif; ?>
    
    <br>
    <a href="index.php">Voltar</a>
  </div>
</body>
</html>
