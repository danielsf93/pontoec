<?php
// pontoec/pontoec.php
session_start();
$host = "localhost";
$dbname = "usuarios01";
$username = "admin";  // Seu usuário do MariaDB
$password = "admin";  // Sua senha do MariaDB

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $usuario = $_POST['usuario'];
        $senha = $_POST['senha'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND senha = :senha");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':senha', $senha);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $_SESSION['usuario'] = $usuario;
            header("Location: index.php");
            exit();
        } else {
            echo "Usuário ou senha incorretos!";
        }
    }
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
