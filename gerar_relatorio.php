<?php
session_start();

$host = "localhost";
$dbname = "usuarios01";
$username = "admin";
$password = "admin";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Restrição de acesso: somente admin pode acessar
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario'] !== 'admin') {
        header("Location: index.php");
        exit();
    }

    // Buscar lista de usuários com registros de ponto (para o dropdown)
    $stmtUsers = $pdo->query("SELECT DISTINCT usuario FROM pontos ORDER BY usuario ASC");
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // Se um usuário foi selecionado via GET, processa os dados
    if (isset($_GET['usuario']) && !empty($_GET['usuario'])) {
        $selectedUser = $_GET['usuario'];

        // Busca todos os pontos do usuário, ordenados cronologicamente
        $stmt = $pdo->prepare("SELECT * FROM pontos WHERE usuario = :usuario ORDER BY data_hora ASC");
        $stmt->execute([':usuario' => $selectedUser]);
        $points = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Processar os pontos para agrupar por data
        $dailyData = [];
        foreach ($points as $p) {
            $date = date("Y-m-d", strtotime($p['data_hora']));
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = ['entradas' => [], 'saidas' => [], 'total_seconds' => 0];
            }
            if ($p['tipo'] == 'Entrada') {
                $dailyData[$date]['entradas'][] = strtotime($p['data_hora']);
            } elseif ($p['tipo'] == 'Saída') {
                $dailyData[$date]['saidas'][] = strtotime($p['data_hora']);
            }
        }

        // Para cada dia, parear as entradas com as saídas (na ordem) e somar a diferença
        foreach ($dailyData as $date => $data) {
            $entradas = $data['entradas'];
            $saidas = $data['saidas'];
            $total = 0;
            $pairs = min(count($entradas), count($saidas));
            for ($i = 0; $i < $pairs; $i++) {
                if ($saidas[$i] > $entradas[$i]) {
                    $total += ($saidas[$i] - $entradas[$i]);
                }
            }
            $dailyData[$date]['total_seconds'] = $total;
        }

        // Preparar dados para o gráfico de frequência (por hora)
        $entryFrequency = array_fill(0, 24, 0);
        $exitFrequency  = array_fill(0, 24, 0);
        foreach ($points as $p) {
            $hour = (int) date("G", strtotime($p['data_hora'])); // hora de 0 a 23
            if ($p['tipo'] == 'Entrada') {
                $entryFrequency[$hour]++;
            } elseif ($p['tipo'] == 'Saída') {
                $exitFrequency[$hour]++;
            }
        }

        // Preparar dados para o gráfico de linha: horas trabalhadas por dia
        $dailyHours = [];
        foreach ($dailyData as $date => $data) {
            $hours = $data['total_seconds'] / 3600;
            $dailyHours[$date] = round($hours, 2);
        }
        $dates = array_keys($dailyHours);
        sort($dates); // ordenar cronologicamente
        $hoursData = [];
        foreach ($dates as $d) {
            $hoursData[] = $dailyHours[$d];
        }
    }
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerar Relatório</title>
  <link rel="stylesheet" href="style.css">
  <!-- Chart.js via CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="container">
    <h2>Gerar Relatório de Pontos</h2>
    <p>Administrador: <strong><?= htmlspecialchars($_SESSION['usuario']) ?></strong></p>
    
    <!-- Formulário para selecionar o usuário -->
    <form method="GET">
      <label for="usuario">Selecione o Usuário:</label>
      <select name="usuario" id="usuario" required>
        <option value="">Selecione...</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= htmlspecialchars($u['usuario']) ?>" <?= (isset($selectedUser) && $selectedUser == $u['usuario']) ? 'selected' : '' ?>>
             <?= htmlspecialchars($u['usuario']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Gerar Relatório</button>
    </form>
    
    <?php if (isset($selectedUser)): ?>
      <h3>Relatório para o Usuário: <?= htmlspecialchars($selectedUser) ?></h3>
      
      <!-- Tabela: Horas Trabalhadas por Dia -->
      <h4>Horas Trabalhadas por Dia</h4>
      <table border="1">
         <tr>
           <th>Data</th>
           <th>Entrada(s)</th>
           <th>Saída(s)</th>
           <th>Total Horas Trabalhadas</th>
         </tr>
         <?php foreach ($dailyData as $date => $data): ?>
           <tr>
             <td><?= date("d/m/Y", strtotime($date)) ?></td>
             <td>
                <?php
                // Exibe as entradas do dia, formatadas como HH:MM:SS
                $entradasFormatted = array_map(function($t){ return date("H:i:s", $t); }, $data['entradas']);
                echo implode(", ", $entradasFormatted);
                ?>
             </td>
             <td>
                <?php
                $saidasFormatted = array_map(function($t){ return date("H:i:s", $t); }, $data['saidas']);
                echo implode(", ", $saidasFormatted);
                ?>
             </td>
             <td><?= number_format($data['total_seconds']/3600, 2) ?> horas</td>
           </tr>
         <?php endforeach; ?>
      </table>
      
      <!-- Gráfico: Frequência de Horários de Entrada e Saída -->
      <h4>Frequência de Horários</h4>
      <canvas id="horariosChart" width="400" height="200"></canvas>
      
      <!-- Gráfico: Horas Trabalhadas por Dia (Linha) -->
      <h4>Horas Trabalhadas por Dia</h4>
      <canvas id="horasChart" width="400" height="200"></canvas>
      
      <script>
         // Gráfico de barras: Frequência de Horários
         const ctxHorarios = document.getElementById('horariosChart').getContext('2d');
         const horariosChart = new Chart(ctxHorarios, {
           type: 'bar',
           data: {
             labels: Array.from({length: 24}, (_, i) => i + ":00"),
             datasets: [{
               label: 'Entradas',
               data: <?= json_encode($entryFrequency) ?>,
               backgroundColor: 'rgba(54, 162, 235, 0.5)',
               borderColor: 'rgba(54, 162, 235, 1)',
               borderWidth: 1
             },
             {
               label: 'Saídas',
               data: <?= json_encode($exitFrequency) ?>,
               backgroundColor: 'rgba(255, 99, 132, 0.5)',
               borderColor: 'rgba(255, 99, 132, 1)',
               borderWidth: 1
             }]
           },
           options: {
             scales: {
               y: {
                 beginAtZero: true,
                 ticks: { stepSize: 1 }
               }
             }
           }
         });
         
         // Gráfico de linha: Horas Trabalhadas por Dia
         const ctxHoras = document.getElementById('horasChart').getContext('2d');
         const horasChart = new Chart(ctxHoras, {
           type: 'line',
           data: {
             labels: <?= json_encode($dates) ?>,
             datasets: [{
               label: 'Horas Trabalhadas',
               data: <?= json_encode($hoursData) ?>,
               fill: false,
               borderColor: 'rgba(75, 192, 192, 1)',
               tension: 0.1
             }]
           },
           options: {
             scales: {
               y: {
                 beginAtZero: true,
                 title: { display: true, text: 'Horas' }
               },
               x: {
                 title: { display: true, text: 'Data' }
               }
             }
           }
         });
      </script>
      
    <?php endif; ?>
    
    <br>
    <a href="index.php">Voltar</a>
  </div>
</body>
</html>
