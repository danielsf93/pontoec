<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Página Inicial</title>
</head>
<body>
    <h2>Bem-vindo, <?php echo isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Visitante'; ?>!</h2>

    <?php if (isset($_SESSION['usuario'])): ?>
        <form action="logout.php" method="POST">
            <button type="submit">Logoff</button>
        </form>
        <?php if ($_SESSION['usuario'] === 'admin'): ?>
            <a href="admin.php">Administração</a>
        <?php endif; ?>
    <?php else: ?>
        <form action="pontoec.php" method="POST">
            Usuário: <input type="text" name="usuario" required>
            Senha: <input type="password" name="senha" required>
            <button type="submit">Login</button>
        </form>
    <?php endif; ?>
</body>
</html>
