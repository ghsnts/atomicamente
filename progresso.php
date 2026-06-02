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
$primeiro_nome = explode(' ', trim($nome_aluno))[0]; // Para um trato mais próximo na Hero Section

try {
    // 1. Puxar as preferências de meta e foco do utilizador do banco de dados
    $stmtUser = $pdo->prepare("SELECT meta_diaria, frente_foco, streak FROM users WHERE id = :uid");
    $stmtUser->execute([':uid' => $user_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $meta_diaria = $userData['meta_diaria'] ?? 20; // Padrão de 20 questões
    $frente_foco = $userData['frente_foco'] ?? '';
    $streak_aluno = $userData['streak'] ?? 0; // Puxa o foguinho!

    // 2. Conta o total de questões respondidas pelo aluno real
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid");
    $stmtTotal->execute([':uid' => $user_id]);
    $totalRespondidas = $stmtTotal->fetchColumn();

    // 3. Conta quantas ele acertou
    $stmtAcertos = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND foi_correta = 1");
    $stmtAcertos->execute([':uid' => $user_id]);
    $totalAcertos = $stmtAcertos->fetchColumn();

    // 4. Calcular exercícios feitos HOJE (Ajuste seguro)
    try {
        $stmtHoje = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND DATE(respondido_em) = CURDATE()");
        $stmtHoje->execute([':uid' => $user_id]);
        $totalHoje = $stmtHoje->fetchColumn();
    } catch (Exception $e) {
        $totalHoje = min($totalRespondidas, 5); // Fallback
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
    /* INTEGRAÇÃO DO DESIGN SYSTEM PREMIUM COM OS SEUS ASSETS */
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); margin: 0; }
    
    /* Cabeçalho */
    .topo-dash { border-bottom: 1px solid var(--borda); background: var(--bg-card); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .nav-dash { padding: 12px 0; }
    .marca-dash { font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--texto-principal); letter-spacing: -0.03em; }
    .badge-enem { font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; color: white; background: var(--texto-secundario); letter-spacing: 0.05em; }

    /* Hero Section (Boas-vindas Premium) */
    .hero-section { background: linear-gradient(135deg, var(--bg-card), rgba(139, 92, 246, 0.03)); border-radius: 24px; padding: 45px 50px; border: 1px solid var(--borda); box-shadow: 0 10px 30px -5px rgba(0,0,0,0.03); margin-bottom: 40px; position: relative; overflow: hidden; }
    .saudacao { font-size: 2.6rem; font-weight: 800; letter-spacing: -0.04em; margin: 0 0 10px 0; color: var(--texto-principal); }
    .mensagem-motivacional { font-size: 1.1rem; color: var(--texto-secundario); margin: 0; line-height: 1.5; max-width: 650px; font-weight: 500; }

    /* Grid de Estatísticas Elevado */
    .grid-estatisticas { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 40px; }
    .card-estatistica { background-color: var(--bg-card); padding: 35px 25px; border-radius: 20px; border: 1px solid var(--borda); box-shadow: 0 4px 20px -5px rgba(0,0,0,0.03); transition: transform 0.3s ease; text-align: center; }
    .card-estatistica:hover { transform: translateY(-5px); }
    
    .meta-progresso-container { margin-top: 20px; text-align: left; background: var(--bg-global); padding: 15px; border-radius: 12px; border: 1px solid var(--borda); }
    .meta-barra-bg { background: rgba(139, 92, 246, 0.1); height: 10px; border-radius: 6px; overflow: hidden; width: 100%; margin-top: 8px; }
    .meta-barra-fill { background: linear-gradient(90deg, var(--roxo-base), #4f46e5); height: 100%; border-radius: 6px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }

    /* Layout Gráfico + Sugestão */
    .dashboard-layout { display: grid; grid-template-columns: 1fr 380px; gap: 30px; align-items: start; margin-bottom: 50px; }
    @media (max-width: 950px) { .dashboard-layout { grid-template-columns: 1fr; } }
    
    .card-principal { background-color: var(--bg-card); padding: 40px; border-radius: 24px; border: 1px solid var(--borda); box-shadow: 0 4px 20px -5px rgba(0,0,0,0.02); height: 100%; }

    /* Card de Sugestão Premium */
    .card-sugestao { background: linear-gradient(145deg, rgba(217, 119, 6, 0.05), var(--bg-card)); border: 1px solid rgba(217, 119, 6, 0.2); padding: 40px 35px; border-radius: 24px; box-shadow: 0 10px 30px -5px rgba(217, 119, 6, 0.05); height: 100%; display: flex; flex-direction: column; }
    
    .btn-acao { display: block; text-align: center; background: var(--roxo-base); color: white; padding: 14px; border-radius: 12px; font-weight: 700; text-decoration: none; font-size: 0.95rem; transition: all 0.2s ease; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2); }
    .btn-acao:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3); color: white; }
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

        <!-- Badge de Ofensiva Integrada -->
        <div style="display: flex; align-items: center; gap: 6px; background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.3); padding: 8px 14px; border-radius: 10px; font-weight: 800; color: #ea580c; font-size: 0.9rem;">
          🔥 <?php echo $streak_aluno; ?> Dias
        </div>
          
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
            👤 <?php echo $primeiro_nome; ?> <span style="font-size: 0.65rem;">▼</span>
          </button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <div style="padding: 10px; font-size: 0.75rem; color: var(--texto-secundario); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Minha Conta</div>
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
    
    <!-- HERO SECTION ADICIONADA -->
    <div class="hero-section">
      <h1 class="saudacao">Olá, <?php echo $primeiro_nome; ?>! 👋</h1>
      <p class="mensagem-motivacional">
        <?php if ($streak_aluno > 0): ?>
          Excelente! Você está com uma ofensiva de <strong><?php echo $streak_aluno; ?> dias</strong>. Continue a resolver exercícios hoje para manter a sua chama acesa e avançar na sua proficiência.
        <?php else: ?>
          Pronto para iniciar os estudos? Resolva a sua primeira bateria de exercícios hoje para acender a sua ofensiva e ganhar as suas primeiras medalhas.
        <?php endif; ?>
      </p>
    </div>

    <!-- GRID DE ESTATÍSTICAS REFINADO -->
    <div class="grid-estatisticas">
      <div class="card-estatistica">
        <span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">📝</span>
        <h3 style="font-size: 2.2rem; margin: 0 0 5px 0; color: var(--texto-principal); font-weight: 800; letter-spacing: -0.03em;"><?php echo $totalRespondidas; ?></h3>
        <p style="color: var(--texto-secundario); font-size: 0.95rem; margin: 0; font-weight: 600;">Questões Resolvidas</p>
        
        <!-- Barra de Progresso Gamificada Intacta -->
        <div class="meta-progresso-container">
          <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700; color: var(--texto-secundario);">
            <span>Meta de Hoje:</span>
            <span style="color: var(--roxo-base);"><?php echo $totalHoje; ?> / <?php echo $meta_diaria; ?></span>
          </div>
          <div class="meta-barra-bg">
            <div class="meta-barra-fill" style="width: <?php echo $percentagem_meta; ?>%;"></div>
          </div>
        </div>
      </div>
      
      <div class="card-estatistica" style="display: flex; flex-direction: column; justify-content: center;">
        <span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">🎯</span>
        <h3 style="font-size: 2.5rem; margin: 0 0 5px 0; color: #10b981; font-weight: 800; letter-spacing: -0.03em;"><?php echo $totalAcertos; ?></h3>
        <p style="color: var(--texto-secundario); font-size: 0.95rem; margin: 0; font-weight: 600;">Acertos Confirmados</p>
      </div>

      <div class="card-estatistica" style="display: flex; flex-direction: column; justify-content: center;">
        <span style="font-size: 2.5rem; display: block; margin-bottom: 10px;">⚡</span>
        <h3 style="font-size: 2.5rem; margin: 0 0 5px 0; color: var(--roxo-base); font-weight: 800; letter-spacing: -0.03em;"><?php echo $proficiencia_geral; ?>%</h3>
        <p style="color: var(--texto-secundario); font-size: 0.95rem; margin: 0; font-weight: 600;">Proficiência Geral</p>
      </div>
    </div>

    <!-- ÁREA DE MAPEAMENTO E SUGESTÃO INTACTAS -->
    <div class="dashboard-layout">
      
      <div class="card-principal">
        <h3 style="margin: 0 0 35px 0; color: var(--texto-principal); font-weight: 800; font-size: 1.3rem; letter-spacing: -0.02em;">📊 Mapeamento de Proficiência em Química</h3>
        <div style="max-height: 400px; position: relative; display: flex; justify-content: center;">
          <canvas id="graficoProficiencia" style="max-width: 380px; max-height: 380px;"></canvas>
        </div>
      </div>

      <aside class="card-sugestao">
        <h3 style="color: #b45309; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; margin: 0 0 20px 0; font-weight: 800; letter-spacing: -0.02em;">
          <span>💡</span> Sugestão Pedagógica
        </h3>
        
        <div style="flex-grow: 1;">
        <?php 
        // LÓGICA DE DIRECIONAMENTO CONTEXTUAL INTACTA
        if (!empty($frente_foco)): 
            if ($frente_foco === 'geral'): ?>
                <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">
                  Definiste <strong>Química Geral e Atomística</strong> como o teu foco. Dominar as forças intermoleculares e a tabela periódica trará pontos fáceis no ENEM!
                </p>
                <a href="topico.php?id=modelos-atomicos" class="btn-acao" style="background: #d97706;">Avançar: Atomística</a>
            <?php elseif ($frente_foco === 'fisico'): ?>
                <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">
                  O teu foco atual é <strong>Físico-Química</strong>. Que tal desvendar os cálculos de Estequiometria e Termoquímica hoje?
                </p>
                <a href="topico.php?id=estequiometria" class="btn-acao" style="background: #b45309;">Praticar Cálculos</a>
            <?php elseif ($frente_foco === 'organica'): ?>
                <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">
                  Foco em <strong>Química Orgânica</strong> detetado! Revisar as funções oxigenadas e a hibridação do carbono é essencial.
                </p>
                <a href="topico.php?id=funcoes-organicas" class="btn-acao" style="background: #7c3aed;">Ver Cadeias Carbónicas</a>
            <?php elseif ($frente_foco === 'ambiental'): ?>
                <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">
                  Foco em <strong>Química Ambiental</strong> ativo. Domina os ciclos biogeoquímicos, chuva ácida e tratamento de águas!
                </p>
                <a href="topico.php?id=quimica-ambiental" class="btn-acao" style="background: #059669;">Revisar Impactos Ambientais</a>
            <?php endif; 
        else: 
            // Fallback baseado na proficiência
            if ($proficiencia_geral < 50): ?>
                <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">
                  Identificamos que a tua árvore de fixação em <strong>Química Geral</strong> precisa de uma base mais sólida. Recomendamos iniciar pelos conceitos fundamentais.
                </p>
                <a href="topico.php?id=modelos-atomicos" class="btn-acao" style="background: #d97706;">Estudar Modelos Atómicos</a>
            <?php else: ?>
                <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">
                  Excelente progresso, <?php echo $primeiro_nome; ?>! A tua base em Química Geral está sólida. Escolha um foco no seu perfil para darmos recomendações avançadas!
                </p>
            <?php endif; 
        endif; ?>
        </div>
        
        <a href="materias.php" class="btn-acao" style="background: transparent; border: 2px solid var(--roxo-base); color: var(--roxo-base); margin-top: 20px; box-shadow: none;">Explorar Todos os Tópicos</a>
      </aside>

    </div>
  </main>

  <script>
    // LÓGICA DO CHART.JS INTACTA E ADAPTÁVEL AO DARK MODE
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
                backgroundColor: 'rgba(139, 92, 246, 0.15)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 2.5,
                pointBackgroundColor: 'rgba(139, 92, 246, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(139, 92, 246, 1)'
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    angleLines: { display: true, color: angleColor },
                    grid: { color: gridColor },
                    pointLabels: { 
                        font: { family: 'Inter', size: 13, weight: '700' }, 
                        color: labelColor 
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { display: false }
                }
            }
        }
    });

    // Controlador de Dropdowns
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
