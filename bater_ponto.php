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

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tipo'], $_POST['foto'])) {
        $usuario = $_SESSION['usuario'];
        $dataHora = date("Y-m-d H:i:s");
        $tipo = $_POST['tipo'];
        $fotoBase64 = $_POST['foto'];

        $fotoNome = null;  // Se não capturar a foto, não grava no banco

        if (!empty($fotoBase64)) {
            // Remove o cabeçalho (data:image/png;base64,) e decodifica a imagem
            $fotoData = preg_replace('#^data:image/\w+;base64,#i', '', $fotoBase64);
            $fotoData = base64_decode($fotoData);

            // Gera um nome único para o arquivo de imagem
            $fotoNome = "foto_" . $usuario . "_" . time() . ".png";
            $caminhoFoto = "foto/" . $fotoNome;

            file_put_contents($caminhoFoto, $fotoData);
        }

        $stmt = $pdo->prepare("INSERT INTO pontos (usuario, data_hora, tipo, foto) VALUES (:usuario, :data_hora, :tipo, :foto)");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':data_hora', $dataHora);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->bindParam(':foto', $fotoNome);
        $stmt->execute();

        echo "<script>alert('Ponto registrado com sucesso!'); window.location.href='bater_ponto.php';</script>";
        exit();
    }

    $stmt = $pdo->prepare("SELECT usuario, data_hora, tipo FROM pontos WHERE usuario = :usuario ORDER BY data_hora DESC LIMIT 5");
    $stmt->bindParam(':usuario', $_SESSION['usuario']);
    $stmt->execute();
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
  <title>Bater Ponto</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h2>Bater Ponto</h2>
    <p>Usuário: <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></p>

    <!-- Formulário para bater o ponto -->
    <form method="POST" onsubmit="return capturarFoto(event)">
      <label for="tipo">Tipo de Ponto:</label>
      <select name="tipo" id="tipo" required>
        <option value="Entrada">Entrada</option>
        <option value="Saída">Saída</option>
      </select>
      <!-- Campo oculto para armazenar a foto capturada -->
      <input type="hidden" name="foto" id="foto">
      <button type="submit">Bater Ponto</button>
    </form>

    <h3>Últimos Pontos Registrados</h3>
    <table border="1">
      <tr>
        <th>Usuário</th>
        <th>Data e Hora</th>
        <th>Tipo</th>
      </tr>
      <?php foreach ($pontos as $ponto): ?>
      <tr>
        <td><?= htmlspecialchars($ponto['usuario']) ?></td>
        <td><?= date("d/m/Y - H:i:s", strtotime($ponto['data_hora'])) ?></td>
        <td><?= htmlspecialchars($ponto['tipo']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>

    <h3>Captura da Foto</h3>
    <!-- Elemento para exibir o vídeo da webcam (note o atributo muted para garantir autoplay) -->
    <video id="video" width="320" height="240" autoplay muted></video>
    <!-- Elemento canvas (oculto) para capturar a imagem -->
    <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>

    <br>
    <a href="index.php">Voltar</a>
  </div>

  <!-- Script JavaScript para capturar a foto -->
  <script>
    let video = document.getElementById('video');
    let canvas = document.getElementById('canvas');
    let fotoInput = document.getElementById('foto');
    let cameraPronta = false; // Flag para verificar se a câmera está pronta

    async function ativarCamera() {
      try {
        let stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
        video.play();
        // Usamos o evento "loadeddata" para saber quando os dados estão prontos
        video.addEventListener('loadeddata', function() {
          cameraPronta = true;
        }, { once: true });
      } catch (err) {
        console.error("Erro ao acessar a webcam: ", err);
        alert("É necessário permitir o acesso à câmera para bater o ponto.");
      }
    }

    async function capturarFoto(event) {
      event.preventDefault();

      if (!cameraPronta) {
        alert("Aguarde a câmera carregar.");
        return false;
      }

      let context = canvas.getContext('2d');
      context.drawImage(video, 0, 0, 320, 240);

      // Pequeno delay para garantir que o frame seja capturado
      await new Promise(resolve => setTimeout(resolve, 200));

      let fotoData = canvas.toDataURL('image/png');
      fotoInput.value = fotoData;

      // Opcional: desabilita o botão para evitar múltiplos cliques
      event.target.querySelector('button').disabled = true;

      event.target.submit();
    }

    // Ativa a câmera assim que a página carregar
    window.onload = ativarCamera;
  </script>
</body>
</html>
