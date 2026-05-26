<?php
session_start();
require_once 'config.php';

// Usuário padrão fictício para simulação local
$user_id = 1; 

try {
    // 1. Conta o total de questões respondidas pela aluna
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid");
    $stmtTotal->execute([':uid' => $user_id]);
    $totalRespondidas = $stmtTotal->fetchColumn();

    // 2. Conta quantas ela acertou
    $stmtAcertos = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND foi_correta = 1");
    $stmtAcertos->execute([':uid' => $user_id]);
    $totalAcertos = $stmtAcertos->fetchColumn();

    // 3. Motor de Recomendação: Calcula a proficiência em Química Geral (Sobe 25% por acerto)
    $proficiencia_geral = ($totalAcertos > 0) ? min(100, $totalAcertos * 25) : 30;
    
    // Valores base simulados para as outras frentes enquanto não há questões delas
    $proficiencia_organica = 65;
    $proficiencia_fisico = 40;

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
  <link rel="stylesheet" href="css/plataforma.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="dash-body">
  
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="index.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 30px; border-radius: 6px;" />
        Atomicamente <small class="badge-enem">ENEM</small>
      </a>
      <div class="user-info">
        <span style="margin-right: 15px; color: var(--cinza-texto);">Olá, <strong>Estudante</strong></span>
        <a href="materias.php" class="btn-estudar" style="background: var(--roxo-base); color:white; padding: 8px 16px; border-radius: 8px; text-decoration:none; font-weight:bold; font-size:0.9rem;">Ver Matérias</a>
      </div>
    </div>
  </header>

  <main class="container" style="padding: 40px 0;">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px;">
      <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--borda); text-align: center;">
        <span style="font-size: 2rem;">📝</span>
        <h3 style="font-size: 1.8rem; margin: 10px 0 5px 0; color: var(--roxo-base);"><?php echo $totalRespondidas; ?></h3>
        <p style="color: var(--cinza-texto); font-size: 0.9rem; font-weight: 500;">Questões Respondidas</p>
      </div>
      
      <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--borda); text-align: center;">
        <span style="font-size: 2rem;">🎯</span>
        <h3 style="font-size: 1.8rem; margin: 10px 0 5px 0; color: #16a34a;"><?php echo $totalAcertos; ?></h3>
        <p style="color: var(--cinza-texto); font-size: 0.9rem; font-weight: 500;">Acertos Confirmados</p>
      </div>

      <div style="background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--borda); text-align: center;">
        <span style="font-size: 2rem;">⚡</span>
        <h3 style="font-size: 1.8rem; margin: 10px 0 5px 0; color: var(--roxo-vivo);"><?php echo $proficiencia_geral; ?>%</h3>
        <p style="color: var(--cinza-texto); font-size: 0.9rem; font-weight: 500;">Proficiência em Química Geral</p>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px; align-items: start;">
      
      <div style="background: white; padding: 30px; border-radius: 16px; border: 1px solid var(--borda);">
        <h3 style="margin-bottom: 20px; color: var(--roxo-base);">📊 Mapeamento Quântico de Desempenho</h3>
        <div style="max-height: 380px; position: relative; display: flex; justify-content: center;">
          <canvas id="graficoProficiencia" style="max-width: 400px; max-height: 400px;"></canvas>
        </div>
      </div>

      <aside style="background: #fdfbf7; border: 1px solid #f5eae0; padding: 25px; border-radius: 16px;">
        <h3 style="color: #b45309; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
          <span>💡</span> Sugestão de Foco Pedagógico
        </h3>
        
        <?php if ($proficiencia_geral < 60): ?>
            <p style="margin-top: 12px; font-size: 0.95rem; line-height: 1.6; color: #78350f;">
              Detetámos uma oscilação na tua árvore de fixação em <strong>Química Geral</strong>. Recomendamos rever os conceitos de base da matéria.
            </p>
            <div style="margin-top: 20px;">
              <a href="topico.php?id=modelos-atomicos" style="display: block; text-align: center; background: #eab308; color: #451a03; padding: 12px; border-radius: 8px; font-weight: bold; text-decoration: none; font-size: 0.9rem;">Reforçar Modelos Atómicos</a>
            </div>
        <?php else: ?>
            <p style="margin-top: 12px; font-size: 0.95rem; line-height: 1.6; color: #14532d;">
              Excelente! A tua proficiência em Química Geral está sólida. Que tal avançares para novos desafios em Físico-Química?
            </p>
            <div style="margin-top: 20px;">
              <a href="materias.php" style="display: block; text-align: center; background: #16a34a; color: white; padding: 12px; border-radius: 8px; font-weight: bold; text-decoration: none; font-size: 0.9rem;">Avançar na Plataforma</a>
            </div>
        <?php endif; ?>
      </aside>

    </div>
  </main>

  <script>
    // Configuração Nativa do Chart.js cruzando dados com as variáveis processadas pelo PHP
    const ctx = document.getElementById('graficoProficiencia').getContext('2d');
    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: ['Química Geral', 'Química Orgânica', 'Físico-Química'],
            datasets: [{
                label: 'A tua Proficiência Atual (%)',
                data: [
                    <?php echo $proficiencia_geral; ?>, 
                    <?php echo $proficiencia_organica; ?>, 
                    <?php echo $proficiencia_fisico; ?>
                ],
                backgroundColor: 'rgba(139, 92, 246, 0.2)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 3,
                pointBackgroundColor: 'rgba(109, 40, 217, 1)'
            }]
        },
        options: {
            scales: {
                r: {
                    angleLines: { display: true },
                    suggestedMin: 0,
                    suggestedMax: 100
                }
            }
        }
    });
  </script>
</body>
</html>
