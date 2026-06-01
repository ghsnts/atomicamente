<?php
session_start();
require_once 'config.php';

// Proteção: Se a pessoa não fez login, expulsa-a para a página de login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; 
$nome_aluno = htmlspecialchars($_SESSION['user_nome']);

try {
    // 1. Conta o total de questões respondidas pelo aluno real
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid");
    $stmtTotal->execute([':uid' => $user_id]);
    $totalRespondidas = $stmtTotal->fetchColumn();

    // 2. Conta quantas ele acertou
    $stmtAcertos = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND foi_correta = 1");
    $stmtAcertos->execute([':uid' => $user_id]);
    $totalAcertos = $stmtAcertos->fetchColumn();

    // 3. Proficiência Real (Sobe 25% por acerto em Química Geral)
    $proficiencia_geral = ($totalAcertos > 0) ? min(100, $totalAcertos * 25) : 0;
    
    // Valores zerados para as outras frentes até termos questões delas
    $proficiencia_organica = 0;
    $proficiencia_fisico = 0;

} catch (PDOException $e) {
    die("Erro ao carregar o Painel: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel de Desempenho | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="css/plataforma.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>
    /* Estilos específicos deste painel para complementar o CSS geral */
    .grid-estatisticas {
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
      gap: 24px; 
      margin-bottom: 35px;
    }
    .dashboard-layout {
      display: grid; 
      grid-template-columns: 1fr 360px; 
      gap: 30px; 
      align-items: start;
    }
    @media (max-width: 900px) {
      .dashboard-layout { grid-template-columns: 1fr; }
    }
    .card-principal {
      background: white; 
      padding: 30px; 
      border-radius: 16px; 
      border: 1px solid var(--borda);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .card-sugestao {
      background: #fffdfa; 
      border: 1px solid #fef3c7; 
      padding: 25px; 
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .btn-acao {
      display: block; 
      text-align: center; 
      background: var(--roxo-base); 
      color: white; 
      padding: 12px; 
      border-radius: 10px; 
      font-weight: 600; 
      text-decoration: none; 
      font-size: 0.95rem;
      transition: background 0.2s;
    }
    .btn-acao:hover { background: var(--roxo-vivo); }
    .btn-logout {
      color: #ef4444; 
      text-decoration: none; 
      font-weight: 500; 
      font-size: 0.9rem;
      padding: 6px 12px;
      border-radius: 6px;
      transition: background 0.2s;
    }
    .btn-logout:hover { background: #fef2f2; }
  </style>
</head>
<body class="dash-body">
  
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem">ENEM</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 20px;">
        <?php if (verificarSeEhAdmin()): ?>
          <a href="admin.php" class="btn-acao" style="background: #7c3aed; color: white; padding: 8px 16px; font-size: 0.85rem; border-radius: 8px; text-decoration: none; font-weight: 700; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">
            ⚙️ Painel Administradora
          </a>
        <?php endif; ?>

        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Painel Inicial</a>
      </div>
    </div>
  </header>

  <main class="container" style="padding: 40px 0;">
    
    <div class="grid-estatisticas">
      <div class="card-estatistica" style="text-align: center;">
        <span style="font-size: 2.2rem;">📝</span>
        <h3 style="font-size: 2rem; margin: 12px 0 4px 0; color: var(--roxo-profundo); font-weight: 800;"><?php echo $totalRespondidas; ?></h3>
        <p style="color: var(--cinza-texto); font-size: 0.9rem; margin: 0; font-weight: 500;">Questões Respondidas</p>
      </div>
      
      <div class="card-estatistica" style="text-align: center;">
        <span style="font-size: 2.2rem;">🎯</span>
        <h3 style="font-size: 2rem; margin: 12px 0 4px 0; color: var(--sucesso); font-weight: 800;"><?php echo $totalAcertos; ?></h3>
        <p style="color: var(--cinza-texto); font-size: 0.9rem; margin: 0; font-weight: 500;">Acertos Confirmados</p>
      </div>

      <div class="card-estatistica" style="text-align: center;">
        <span style="font-size: 2.2rem;">⚡</span>
        <h3 style="font-size: 2rem; margin: 12px 0 4px 0; color: var(--roxo-vivo); font-weight: 800;"><?php echo $proficiencia_geral; ?>%</h3>
        <p style="color: var(--cinza-texto); font-size: 0.9rem; margin: 0; font-weight: 500;">Proficiência Geral</p>
      </div>
    </div>

    <div class="dashboard-layout">
      
      <div class="card-principal">
        <h3 style="margin: 0 0 25px 0; color: var(--roxo-profundo); font-weight: 700; font-size: 1.15rem;">📊 Mapeamento de Proficiência</h3>
        <div style="max-height: 380px; position: relative; display: flex; justify-content: center;">
          <canvas id="graficoProficiencia" style="max-width: 360px; max-height: 360px;"></canvas>
        </div>
      </div>

      <aside class="card-sugestao">
        <h3 style="color: #b45309; font-size: 1.05rem; display: flex; align-items: center; gap: 8px; margin: 0 0 12px 0; font-weight: 700;">
          <span>💡</span> Sugestão de Foco Pedagógico
        </h3>
        
        <?php if ($proficiencia_geral < 50): ?>
            <p style="font-size: 0.95rem; line-height: 1.6; color: #78350f; margin: 0 0 20px 0;">
              Identificamos que a tua árvore de fixação em <strong>Química Geral</strong> precisa de uma base mais sólida. Recomendamos iniciar pelos conceitos fundamentais do átomo.
            </p>
            <a href="topico.php?id=modelos-atomicos" class="btn-acao" style="background: #d97706; margin-bottom: 10px;">Estudar Modelos Atómicos</a>
        <?php else: ?>
            <p style="font-size: 0.95rem; line-height: 1.6; color: #14532d; margin: 0 0 20px 0;">
              Excelente progresso! A tua base em Química Geral está a expandir-se. Que tal explorares novas frentes de estudo?
            </p>
        <?php endif; ?>
        
        <a href="materias.php" class="btn-acao" style="background: transparent; border: 2px solid var(--roxo-base); color: var(--roxo-base);">Ver Todos os Tópicos</a>
      </aside>

    </div>
  </main>

  <script>
    const ctx = document.getElementById('graficoProficiencia').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Química Geral', 'Química Orgânica', 'Físico-Química'],
            datasets: [{
                label: 'O teu nível atual (%)',
                data: [
                    <?php echo $proficiencia_geral; ?>, 
                    <?php echo $proficiencia_organica; ?>, 
                    <?php echo $proficiencia_fisico; ?>
                ],
                backgroundColor: 'rgba(109, 40, 217, 0.1)',
                borderColor: 'rgba(109, 40, 217, 1)',
                borderWidth: 2.5,
                pointBackgroundColor: 'rgba(109, 40, 217, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(109, 40, 217, 1)'
            }]
        },
        options: {
            plugins: {
                legend: { display: false } // Oculta a legenda para um visual mais limpo
            },
            scales: {
                r: {
                    angleLines: { display: true, color: '#f1f5f9' },
                    grid: { color: '#e2e8f0' },
                    pointLabels: { font: { family: 'Inter', size: 12, weight: '600' }, color: '#475569' },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { display: false } // Remove os números soltos sobrepostos no gráfico
                }
            }
        }
    });
  </script>
</body>
</html>
