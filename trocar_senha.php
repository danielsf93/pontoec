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

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['nova_senha'])) {
        $nova_senha = $_POST['nova_senha'];
        $usuario = $_SESSION['usuario'];

        if (!empty($nova_senha)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE usuario = :usuario");
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':senha', $nova_senha);
            $stmt->execute();

            echo "Senha alterada com sucesso!";
        } else {
            echo "Erro: A senha não pode estar vazia.";
        }
    }
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trocar Senha</title>
</head>
<body>
    <h2>Trocar Senha</h2>
    <form method="POST">
        Nova senha: <input type="password" name="nova_senha" required>
        <button type="submit">Alterar Senha</button>
    </form>
    <br>
    <a href="index.php">Voltar</a>
</body>
</html>
