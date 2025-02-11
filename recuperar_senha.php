<?php
session_start();

$host = "localhost";
$dbname = "usuarios01";
$username = "admin";
$password = "admin";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['usuario'])) {
        $usuario = $_POST['usuario'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

       

        if ($user) {
            $stmt = $pdo->prepare("UPDATE usuarios SET recuperar_senha = 1, data_solicitacao = NOW() WHERE usuario = :usuario");
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            
            echo "Solicitação enviada. Aguarde o administrador redefinir sua senha.";
        } else {
            echo "Usuário não encontrado.";
        }
        
    }
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recuperar Senha</title>
</head>
<body>
    <h2>Recuperar Senha</h2>
    <form method="POST">
        Usuário: <input type="text" name="usuario" required>
        <button type="submit">Solicitar Redefinição</button>
    </form>
    <br>
    <a href="index.php">Voltar</a>
</body>
</html>
