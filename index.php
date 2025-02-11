<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página Inicial</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Bem-vindo, <?php echo isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Visitante'; ?>!</h2>


      
        
        <?php if (isset($_SESSION['usuario'])): ?>
            <p><a href="bater_ponto.php">Bater ponto</a></p>
        <p><a href="ver_meus_pontos.php">Ver Meus Pontos</a></p>
        <p><a href="pedir_revisao.php">Pedir Revisão</a></p>
            <p><a href="trocar_senha.php">Trocar senha</a></p>
            <form action="logout.php" method="POST">
                <button type="submit">Sair</button>
            </form>
            <?php if ($_SESSION['usuario'] === 'admin'): ?>
                <p><a href="admin.php">Administração</a></p>
            <?php endif; ?>
        <?php else: ?>
            <h3>Login</h3>
            <form action="pontoec.php" method="POST">
                <input type="text" name="usuario" placeholder="Usuário" required><br>
                <input type="password" name="senha" placeholder="Senha" required><br>
                <button type="submit">Entrar</button>
            </form>
            <p><a href="recuperar_senha.php">Esqueci minha senha</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
