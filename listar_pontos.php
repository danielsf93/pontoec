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

    // Captura os filtros da URL (se existirem)
    $filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
    $filtro_inicio = isset($_GET['inicio']) ? $_GET['inicio'] : '';
    $filtro_fim = isset($_GET['fim']) ? $_GET['fim'] : '';
    $ordenacao = isset($_GET['ordenacao']) ? $_GET['ordenacao'] : 'DESC';

    // Monta a query dinâmica, agora também selecionando a coluna "foto"
    $query = "SELECT usuario, data_hora, tipo, foto FROM pontos WHERE 1=1";
    $params = [];

    if (!empty($filtro_usuario)) {
        $query .= " AND usuario = :usuario";
        $params[':usuario'] = $filtro_usuario;
    }

    if (!empty($filtro_inicio)) {
        $query .= " AND data_hora >= :inicio";
        $params[':inicio'] = $filtro_inicio . " 00:00:00";
    }

    if (!empty($filtro_fim)) {
        $query .= " AND data_hora <= :fim";
        $params[':fim'] = $filtro_fim . " 23:59:59";
    }

    $query .= " ORDER BY data_hora $ordenacao";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar todos os usuários para o filtro de seleção
    $stmtUsuarios = $pdo->query("SELECT DISTINCT usuario FROM pontos ORDER BY usuario ASC");
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Pontos</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos para o modal (popup) */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
            position: relative;
        }
        .close {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Listar Todos os Pontos</h2>
        <p>Administrador: <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></p>

        <!-- Filtros -->
        <form method="GET">
            <label for="usuario">Usuário:</label>
            <select name="usuario" id="usuario">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $user): ?>
                    <option value="<?= htmlspecialchars($user['usuario']) ?>" <?= ($filtro_usuario == $user['usuario']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['usuario']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="inicio">Data Inicial:</label>
            <input type="date" name="inicio" id="inicio" value="<?= htmlspecialchars($filtro_inicio) ?>">

            <label for="fim">Data Final:</label>
            <input type="date" name="fim" id="fim" value="<?= htmlspecialchars($filtro_fim) ?>">

            <label for="ordenacao">Ordenação:</label>
            <select name="ordenacao" id="ordenacao">
                <option value="DESC" <?= ($ordenacao == 'DESC') ? 'selected' : '' ?>>Mais recente primeiro</option>
                <option value="ASC" <?= ($ordenacao == 'ASC') ? 'selected' : '' ?>>Mais antigo primeiro</option>
            </select>

            <button type="submit">Filtrar</button>
        </form>

        <!-- Tabela com os registros -->
        <?php if (count($pontos) > 0): ?>
            <table border="1">
                <tr>
                    <th>Usuário</th>
                    <th>Data e Hora</th>
                    <th>Tipo</th>
                    <th>Foto</th>
                </tr>
                <?php foreach ($pontos as $ponto): ?>
                    <tr>
                        <td><?= htmlspecialchars($ponto['usuario']) ?></td>
                        <td><?= date("d/m/Y - H:i:s", strtotime($ponto['data_hora'])) ?></td>
                        <td><?= htmlspecialchars($ponto['tipo']) ?></td>
                        <td>
                            <?php if(!empty($ponto['foto'])): ?>
                                <!-- Ao clicar, abre o modal com a foto, nome e data/hora -->
                                <a href="#" onclick="abrirModal('foto/<?= htmlspecialchars($ponto['foto']) ?>', '<?= htmlspecialchars($ponto['usuario']) ?>', '<?= date("d/m/Y - H:i:s", strtotime($ponto['data_hora'])) ?>'); return false;">Ver Foto</a>
                            <?php else: ?>
                                Sem foto
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Nenhum registro encontrado com os filtros aplicados.</p>
        <?php endif; ?>

        <br>
        <a href="index.php">Voltar</a>
    </div>

    <!-- Modal para exibir a foto com informações -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="fecharModal()">&times;</span>
            <img id="modalFoto" src="" alt="Foto" style="max-width:100%;"><br>
            <p id="modalInfo"></p>
        </div>
    </div>

    <script>
        function abrirModal(fotoUrl, usuario, dataHora) {
            document.getElementById('modalFoto').src = fotoUrl;
            document.getElementById('modalInfo').innerHTML = '<strong>' + usuario + '</strong><br>' + dataHora;
            document.getElementById('modal').style.display = 'block';
        }

        function fecharModal() {
            document.getElementById('modal').style.display = 'none';
        }

        // Fecha o modal se o usuário clicar fora do conteúdo
        window.onclick = function(event) {
            var modal = document.getElementById('modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
