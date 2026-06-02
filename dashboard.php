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
    // 1. Puxar as preferências de meta e foco do utilizador do banco de dados
    $stmtUser = $pdo->prepare("SELECT meta_diaria, frente_foco FROM users WHERE id = :uid");
    $stmtUser->execute([':uid' => $user_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $meta_diaria = $userData['meta_diaria'] ?? 20; // Padrão de 20 questões
    $frente_foco = $userData['frente_foco'] ?? '';

    // 2. Conta o total de questões respondidas pelo aluno real
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid");
    $stmtTotal->execute([':uid' => $user_id]);
    $totalRespondidas = $stmtTotal->fetchColumn();

    // 3. Conta quantas ele acertou
    $stmtAcertos = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND foi_correta = 1");
    $stmtAcertos->execute([':uid' => $user_id]);
    $totalAcertos = $stmtAcertos->fetchColumn();

    // 4. Calcular exercícios feitos HOJE (Ajuste seguro: tenta buscar por data, senão assume uma estimativa baseada no progresso)
    try {
        $stmtHoje = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND DATE(respondido_em) = CURDATE()");
        $stmtHoje->execute([':uid' => $user_id]);
        $totalHoje = $stmtHoje->fetchColumn();
    } catch (Exception $e) {
        $totalHoje = min($totalRespondidas, 5); // Fallback caso a coluna de data ainda precise de ajuste
    }

    $percentagem_meta = ($meta_diaria > 0) ? min(100, ($totalHoje / $meta_diaria) * 100) : 0;

    // 5. Proficiência Real (Sobe 25% por acerto em Química Geral)
    $proficiencia_geral = ($totalAcertos > 0) ? min(100, $totalAcertos * 25) : 0;
    
    // Valores iniciais para as outras frentes
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
    /* Estilos específicos e complementares adaptados para o Design System */
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
      background-color: var(--bg-card); 
      padding: 30px; 
      border-radius: 16px; 
      border: 1px solid var(--borda);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    /* Card de sugestão inteligente e adaptável ao tema claro/escuro */
    .card-sugestao {
      background-color: rgba(217, 119, 6, 0.04); 
      border: 1px solid rgba(217, 119, 6, 0.2); 
      padding: 25px; 
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    
    /* Componente da barra de progresso gamificada */
    .meta-progresso-container { margin-top: 12px; text-align: left; }
    .meta-barra-bg { background: var(--borda); height: 8px; border-radius: 4px; overflow: hidden; width: 100%; margin-top: 5px; }
    .meta-barra-fill { background: var(--roxo-base); height: 100%; border-radius: 4px; transition: width 0.4s ease; }

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
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">
  
  <header class="topo-dash">
    <div class="container nav-dash" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente 
        <?php 
          $pagina_atual = basename($_SERVER['PHP_SELF']);
          if ($pagina_atual === 'topico.php') {
              echo '<span class="badge-enem" style="background: var(--roxo-base);">SALA DE AULA</span>';
          } elseif ($pagina_atual === 'admin.php') {
              echo '<span class="badge-enem" style="background: #ef4444;">PAINEL ADMIN</span>';
          } else {
              echo '<span class="badge-enem">ENEM</span>';
          }
        ?>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        
        <?php if (function_exists('verificarSeEhAdmin') && verificarSeEhAdmin() && $pagina_atual !== 'admin.php'): ?>
          <a href="admin.php" class="btn-acao" style="background: #7c3aed; color: white; padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; text-decoration: none; font-weight: 700; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);">
            ⚙️ Gerenciar
          </a>
        <?php endif; ?>

        <?php if ($pagina_atual === 'admin.php' || $pagina_atual === 'topico.php'): ?>
          <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.88rem; margin-right: 5px;">Painel Inicial</a>
        <?php endif; ?>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
            🛠️ Configurações
          </button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()">
              <span id="btn-tema-texto">🌙 Modo Escuro</span>
            </div>
            <div class="dropdown-item" style="opacity: 0.6; cursor: not-allowed;">
              <span>🔔 Notificações (Breve)</span>
            </div>
            <div class="dropdown-item" style="opacity: 0.6; cursor: not-allowed;">
              <span>📏 Tamanho da Fonte (Breve)</span>
            </div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 14px; font-size: 0.88rem; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            👤 <?php echo explode(' ', $_SESSION['user_nome'] ?? 'Estudante')[0]; ?> <span style="font-size: 0.65rem;">▼</span>
          </button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <div style="padding: 10px; font-size: 0.75rem; color: var(--texto-secundario); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
              Minha Conta
            </div>
            <a href="perfil.php" class="dropdown-item">🧑‍🎓 Preferências do Perfil</a>
            <a href="progresso.php" class="dropdown-item">📈 Meu Progresso ENEM</a>
            <div class="dropdown-divisor"></div>
            <a href="logout.php" class="dropdown-item sair">🚪 Sair da Conta</a>
          </div>
        </div>

      </div>
    </div>
  </header>

  <main class="container" style="padding: 40px 0;">
    
    <div class="grid-estatisticas">
      <div class="card-estatistica" style="text-align: center;">
        <span style="font-size: 2.2rem;">📝</span>
        <h3 style="font-size: 2rem; margin: 12px 0 4px 0; color: var(--roxo-profundo); font-weight: 800;"><?php echo $totalRespondidas; ?></h3>
        <p style="color: var(--texto-secundario); font-size: 0.9rem; margin: 0; font-weight: 500;">Questões Totais</p>
        
        <div class="meta-progresso-container">
          <div style="display: flex; justify-content: space-between; font-size: 0.75rem; font-weight: 600; color: var(--texto-secundario);">
            <span>Meta de hoje:</span>
            <span><?php echo $totalHoje; ?> / <?php echo $meta_diaria; ?></span>
          </div>
          <div class="meta-barra-bg">
            <div class="meta-barra-fill" style="width: <?php echo $percentagem_meta; ?>%;"></div>
          </div>
        </div>
      </div>
      
      <div class="card-estatistica" style="text-align: center;">
        <span style="font-size: 2.2rem;">🎯</span>
        <h3 style="font-size: 2rem; margin: 12px 0 4px 0; color: var(--sucesso); font-weight: 800;"><?php echo $totalAcertos; ?></h3>
        <p style="color: var(--texto-secundario); font-size: 0.9rem; margin: 0; font-weight: 500;">Acertos Confirmados</p>
      </div>

      <div class="card-estatistica" style="text-align: center;">
        <span style="font-size: 2.2rem;">⚡</span>
        <h3 style="font-size: 2rem; margin: 12px 0 4px 0; color: var(--roxo-vivo); font-weight: 800;"><?php echo $proficiencia_geral; ?>%</h3>
        <p style="color: var(--texto-secundario); font-size: 0.9rem; margin: 0; font-weight: 500;">Proficiência Geral</p>
      </div>
    </div>

    <div class="dashboard-layout">
      
      <div class="card-principal">
        <h3 style="margin: 0 0 25px 0; color: var(--texto-principal); font-weight: 700; font-size: 1.15rem;">📊 Mapeamento de Proficiência em Química</h3>
        <div style="max-height: 380px; position: relative; display: flex; justify-content: center;">
          <canvas id="graficoProficiencia" style="max-width: 360px; max-height: 360px;"></canvas>
        </div>
      </div>

      <aside class="card-sugestao">
        <h3 style="color: #b45309; font-size: 1.05rem; display: flex; align-items: center; gap: 8px; margin: 0 0 12px 0; font-weight: 700;">
          <span>💡</span> Sugestão Pedagógica
        </h3>
        
        <?php 
        // LÓGICA DE DIRECIONAMENTO CONTEXTUAL EXCLUSIVA DE QUÍMICA
        if (!empty($frente_foco)): 
            if ($frente_foco === 'geral'): ?>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 20px 0;">
                  Definiste <strong>Química Geral e Atomística</strong> como o teu foco. Dominar as forças intermoleculares e a tabela periódica trará pontos fáceis no ENEM!
                </p>
                <a href="topico.php?id=modelos-atomicos" class="btn-acao" style="background: #d97706; margin-bottom: 10px;">Estudar Atomística</a>
            <?php elseif ($frente_foco === 'fisico'): ?>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 20px 0;">
                  O teu foco atual é <strong>Físico-Química</strong>. Que tal desvendar os cálculos de Estequiometria e Termoquímica hoje?
                </p>
                <a href="topico.php?id=estequiometria" class="btn-acao" style="background: #b45309; margin-bottom: 10px;">Praticar Cálculos</a>
            <?php elseif ($frente_foco === 'organica'): ?>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 20px 0;">
                  Foco em <strong>Química Orgânica</strong> detetado! Revisar as funções oxigenadas e a hibridação do carbono é essencial.
                </p>
                <a href="topico.php?id=funcoes-organicas" class="btn-acao" style="background: #7c3aed; margin-bottom: 10px;">Ver Cadeias Carbónicas</a>
            <?php elseif ($frente_foco === 'ambiental'): ?>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 20px 0;">
                  Foco em <strong>Química Ambiental</strong> ativo. Domina os ciclos biogeoquímicos, chuva ácida e tratamento de águas!
                </p>
                <a href="topico.php?id=quimica-ambiental" class="btn-acao" style="background: #059669; margin-bottom: 10px;">Revisar Impactos Ambientais</a>
            <?php endif; 
        else: 
            // Fallback baseado na proficiência caso ele não tenha escolhido um foco específico ainda
            if ($proficiencia_geral < 50): ?>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 20px 0;">
                  Identificamos que a tua árvore de fixação em <strong>Química Geral</strong> precisa de uma base mais sólida. Recomendamos iniciar pelos conceitos fundamentais.
                </p>
                <a href="topico.php?id=modelos-atomicos" class="btn-acao" style="background: #d97706; margin-bottom: 10px;">Estudar Modelos Atómicos</a>
            <?php else: ?>
                <p style="font-size: 0.95rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 20px 0;">
                  Excelente progresso, <?php echo $nome_aluno; ?>! A tua base em Química Geral está sólida. Escolha um foco no seu perfil para darmos recomendações avançadas!
                </p>
            <?php endif; 
        endif; ?>
        
        <a href="materias.php" class="btn-acao" style="background: transparent; border: 2px solid var(--roxo-base); color: var(--roxo-base);">Ver Todos os Tópicos</a>
      </aside>

    </div>
  </main>

  <script>
    // Injetar variáveis de cor dinâmicas que respeitam o modo noturno para o Chart.js
    const darkActive = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = darkActive ? 'rgba(255,255,255,0.08)' : '#e2e8f0';
    const angleColor = darkActive ? 'rgba(255,255,255,0.05)' : '#f1f5f9';
    const labelColor = darkActive ? '#9ca3af' : '#475569';

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
                legend: { display: false }
            },
            scales: {
                r: {
                    angleLines: { display: true, color: angleColor },
                    grid: { color: gridColor },
                    pointLabels: { 
                        font: { family: 'Inter', size: 12, weight: '600' }, 
                        color: labelColor 
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { display: false }
                }
            }
        }
    });

    // Controlador nativo anti-conflito para os dropdowns
    function alternarDropdown(id) {
        document.querySelectorAll('.dropdown-conteudo').forEach(drop => {
            if(drop.id !== id) drop.classList.remove('mostrar');
        });
        document.getElementById(id).classList.toggle('mostrar');
    }
    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => {
                drop.classList.remove('remove');
                drop.classList.remove('mostrar');
            });
        }
    }
  </script>
</body>
</html>
