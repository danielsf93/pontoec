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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $usuario = $_SESSION['usuario'];
        $dataHora = date("Y-m-d H:i:s");

        $stmt = $pdo->prepare("INSERT INTO pontos (usuario, data_hora) VALUES (:usuario, :data_hora)");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':data_hora', $dataHora);
        $stmt->execute();

        echo "<script>alert('Ponto registrado com sucesso!'); window.location.href='bater_ponto.php';</script>";
        exit();
    }

    $stmt = $pdo->prepare("SELECT usuario, data_hora FROM pontos WHERE usuario = :usuario ORDER BY data_hora DESC LIMIT 5");
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

        <form method="POST">
            <button type="submit">Bater Ponto</button>
        </form>

        <h3>Últimos Pontos Registrados</h3>
        <table border="1">
            <tr>
                <th>Usuário</th>
                <th>Data e Hora</th>
            </tr>
            <?php foreach ($pontos as $ponto): ?>
                <tr>
                    <td><?= htmlspecialchars($ponto['usuario']) ?></td>
                    <td><?= date("d/m/Y - H:i:s", strtotime($ponto['data_hora'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br>
        <a href="index.php">Voltar</a>
    </div>
</body>
</html>
