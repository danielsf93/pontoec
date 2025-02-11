<?php
session_start();

$host = "localhost";
$dbname = "usuarios01";
$username = "admin";  
$password = "admin";  

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
        header("Location: index.php");
        exit();
    }

    // Criar novo usuário
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['novo_usuario'], $_POST['nova_senha'])) {
        $novo_usuario = $_POST['novo_usuario'];
        $nova_senha = $_POST['nova_senha'];

        $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, senha, tipo) VALUES (:usuario, :senha, 'usuario')");
        $stmt->bindParam(':usuario', $novo_usuario);
        $stmt->bindParam(':senha', $nova_senha);
        $stmt->execute();

        header("Location: admin.php");
        exit();
    }
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha, recuperar_senha = 0 WHERE usuario = :usuario");

    // Excluir usuário
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['excluir_usuario'])) {
        $excluir_usuario = $_POST['excluir_usuario'];

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE usuario = :usuario AND usuario != 'admin'");
        $stmt->bindParam(':usuario', $excluir_usuario);
        $stmt->execute();

        header("Location: admin.php");
        exit();
    }

    // Alterar senha
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mudar_senha_usuario'], $_POST['nova_senha_usuario'])) {
        $mudar_usuario = $_POST['mudar_senha_usuario'];
        $nova_senha = $_POST['nova_senha_usuario'];

        if (!empty($nova_senha)) {
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE usuario = :usuario");
            $stmt->bindParam(':usuario', $mudar_usuario);
            $stmt->bindParam(':senha', $nova_senha);
            $stmt->execute();
        }

        header("Location: admin.php");
        exit();
    }

    // Buscar todos os usuários
    $stmt = $pdo->prepare("SELECT usuario, tipo FROM usuarios");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Administração</title>
</head>
<body>
    <h2>Painel Administrativo</h2>
    <p>Bem-vindo, Admin!</p>

    <h3>Criar Novo Usuário</h3>
    <form method="POST">
        Usuário: <input type="text" name="novo_usuario" required>
        Senha: <input type="password" name="nova_senha" required>
        <button type="submit">Criar Usuário</button>
    </form>

    <h3>Lista de Usuários</h3>
    <table border="1">
        <tr>
            <th>Usuário</th>
            <th>Tipo</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                <td><?= htmlspecialchars($usuario['tipo']) ?></td>
                <td>
                    <?php if ($usuario['usuario'] !== 'admin'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="excluir_usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
                            <button type="submit" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="mudar_senha_usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>">
                            <input type="password" name="nova_senha_usuario" placeholder="Nova senha" required>
                            <button type="submit">Alterar Senha</button>
                        </form>
                    <?php else: ?>
                        <em>Admin</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <br>

    <h3>Usuários que solicitaram recuperação de senha</h3>
<table border="1">
    <tr>
        <th>Usuário</th>
        <th>Ações</th>
    </tr>
    <?php
    $stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE recuperar_senha = 1");
    $stmt->execute();
    $recuperacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($recuperacoes as $rec): ?>
        <tr>
            <td><?= htmlspecialchars($rec['usuario']) ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="mudar_senha_usuario" value="<?= htmlspecialchars($rec['usuario']) ?>">
                    <input type="password" name="nova_senha_usuario" placeholder="Nova senha" required>
                    <button type="submit">Redefinir Senha</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<br>
    <a href="index.php">Voltar</a>
</body>
</html>
