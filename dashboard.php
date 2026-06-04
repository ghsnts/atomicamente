<?php
// Inicia a sessão de forma segura e única
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Proteção: Se a pessoa não fez login, expulsa-a para a página de login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; 
$nome_aluno = htmlspecialchars($_SESSION['user_nome'] ?? 'Aluno');
$primeiro_nome = explode(' ', trim($nome_aluno))[0]; // Trato próximo na Hero Section

try {
    // =========================================================================================
    // 1. DADOS GERAIS DO USUÁRIO
    // =========================================================================================
    $stmtUser = $pdo->prepare("SELECT meta_diaria, frente_foco, streak FROM users WHERE id = :uid");
    $stmtUser->execute([':uid' => $user_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $meta_diaria = $userData['meta_diaria'] ?? 20; // Padrão de 20 questões
    $frente_foco = $userData['frente_foco'] ?? '';
    $streak_aluno = $userData['streak'] ?? 0; // Puxa a ofensiva/foguinho

    // =========================================================================================
    // 2. ESTATÍSTICAS DE PROGRESSO
    // =========================================================================================
    // Total de questões respondidas
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid");
    $stmtTotal->execute([':uid' => $user_id]);
    $totalRespondidas = $stmtTotal->fetchColumn();

    // Total de acertos
    $stmtAcertos = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND is_correct = 1");
    $stmtAcertos->execute([':uid' => $user_id]);
    $totalAcertos = $stmtAcertos->fetchColumn();

    // Exercícios feitos HOJE (Ajuste seguro com try-catch)
    try {
        $stmtHoje = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND DATE(respondido_em) = CURDATE()");
        $stmtHoje->execute([':uid' => $user_id]);
        $totalHoje = $stmtHoje->fetchColumn();
    } catch (Exception $e) {
        $totalHoje = min($totalRespondidas, 5); // Fallback caso dê erro de data
    }

    $percentagem_meta = ($meta_diaria > 0) ? min(100, ($totalHoje / $meta_diaria) * 100) : 0;
    
    // Proficiência Geral (%)
    $proficiencia_geral = ($totalRespondidas > 0) ? min(100, round(($totalAcertos / $totalRespondidas) * 100)) : 0;

    // =========================================================================================
    // 3. DADOS DO RADAR (GRÁFICO INTERATIVO) - BUSCANDO DO BANCO DE DADOS REAL
    // =========================================================================================
    
    // 3.1 Busca o desempenho detalhado por SUBTÓPICOS
    // Usa a tabela 'subtopics', onde o texto se chama 'titulo'
    $stmtRadarSub = $pdo->prepare("
        SELECT 
            st.titulo as titulo, 
            COUNT(up.id) as total_respondidas, 
            SUM(up.is_correct) as total_acertos
        FROM user_progress up
        JOIN questions q ON up.question_id = q.id
        JOIN subtopics st ON q.subtopic_id = st.id
        WHERE up.user_id = :uid
        GROUP BY st.id
        ORDER BY total_respondidas DESC 
        LIMIT 6
    ");
    $stmtRadarSub->execute([':uid' => $user_id]);
    $radarRowsSub = $stmtRadarSub->fetchAll(PDO::FETCH_ASSOC);

    $labelsSub = []; 
    $scoresSub = [];
    
    if (count($radarRowsSub) >= 3) {
        foreach ($radarRowsSub as $row) {
            $labelsSub[] = mb_strimwidth($row['titulo'], 0, 18, "..."); 
            $scoresSub[] = round(($row['total_acertos'] / $row['total_respondidas']) * 100);
        }
    } else {
        // Fallback elegante caso o aluno não tenha feito questões suficientes ainda
        $labelsSub = ['Atomística', 'Ligações', 'Funções Org.', 'Estequiometria', 'Soluções'];
        $scoresSub = [$proficiencia_geral, 0, 0, 0, 0];
    }

    // 3.2 Busca o desempenho detalhado por ÁREA GERAL (Subjects)
    // Usa a tabela 'subjects', onde o texto se chama 'nome'
    $stmtRadarTop = $pdo->prepare("
        SELECT 
            sb.nome as titulo, 
            COUNT(up.id) as total_respondidas, 
            SUM(up.is_correct) as total_acertos
        FROM user_progress up
        JOIN questions q ON up.question_id = q.id
        JOIN subtopics st ON q.subtopic_id = st.id
        JOIN subjects sb ON st.subject_id = sb.id
        WHERE up.user_id = :uid
        GROUP BY sb.id
        ORDER BY total_respondidas DESC 
        LIMIT 6
    ");
    $stmtRadarTop->execute([':uid' => $user_id]);
    $radarRowsTop = $stmtRadarTop->fetchAll(PDO::FETCH_ASSOC);

    $labelsTop = []; 
    $scoresTop = [];
    
    if (count($radarRowsTop) >= 3) {
        foreach ($radarRowsTop as $row) {
            $labelsTop[] = mb_strimwidth($row['titulo'], 0, 18, "..."); 
            $scoresTop[] = round(($row['total_acertos'] / $row['total_respondidas']) * 100);
        }
    } else {
        // Fallback elegante para Tópicos Gerais
        $labelsTop = ['Química Geral', 'Físico-Química', 'Orgânica', 'Ambiental', 'Analítica'];
        $scoresTop = [$proficiencia_geral, ($proficiencia_geral > 10 ? $proficiencia_geral - 10 : 0), 0, 0, 0];
    }

} catch (PDOException $e) {
    die("Erro Crítico ao carregar o Painel. Por favor, contate o suporte: " . $e->getMessage());
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
    /* ==========================================================================
       ESTILIZAÇÃO BASE E TYPOGRAFIA
       ========================================================================== */
    body { 
        font-family: 'Inter', sans-serif; 
        background-color: var(--bg-global); 
        color: var(--texto-principal); 
        margin: 0; 
    }
    
    /* ==========================================================================
       CABEÇALHO / NAVBAR 
       ========================================================================== */
    .topo-dash { 
        border-bottom: 1px solid var(--borda); 
        background: var(--bg-card); 
        position: sticky; 
        top: 0; 
        z-index: 100; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
    }
    .nav-dash { 
        padding: 12px 20px; 
        max-width: 1200px; 
        margin: 0 auto; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        width: 100%; 
        box-sizing: border-box; 
    }
    .marca-dash { 
        font-weight: 800; 
        font-size: 1.25rem; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        text-decoration: none; 
        color: var(--texto-principal); 
        letter-spacing: -0.03em; 
    }
    .badge-enem { 
        font-size: 0.7rem; 
        font-weight: 800; 
        padding: 4px 8px; 
        border-radius: 6px; 
        color: white; 
        background: var(--texto-secundario); 
        letter-spacing: 0.05em; 
    }

    /* ==========================================================================
       HERO SECTION (Boas-vindas)
       ========================================================================== */
    .hero-section { 
        background: linear-gradient(135deg, var(--bg-card), rgba(139, 92, 246, 0.03)); 
        border-radius: 24px; 
        padding: 45px 50px; 
        border: 1px solid var(--borda); 
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.03); 
        margin-bottom: 40px; 
        position: relative; 
        overflow: hidden; 
    }
    .saudacao { 
        font-size: 2.6rem; 
        font-weight: 800; 
        letter-spacing: -0.04em; 
        margin: 0 0 10px 0; 
        color: var(--texto-principal); 
    }
    .mensagem-motivacional { 
        font-size: 1.1rem; 
        color: var(--texto-secundario); 
        margin: 0; 
        line-height: 1.5; 
        max-width: 650px; 
        font-weight: 500; 
    }

    /* ==========================================================================
       GRID DE ESTATÍSTICAS
       ========================================================================== */
    .grid-estatisticas { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
        gap: 24px; 
        margin-bottom: 40px; 
    }
    .card-estatistica { 
        background-color: var(--bg-card); 
        padding: 35px 25px; 
        border-radius: 20px; 
        border: 1px solid var(--borda); 
        box-shadow: 0 4px 20px -5px rgba(0,0,0,0.03); 
        transition: transform 0.3s ease; 
        text-align: center; 
    }
    .card-estatistica:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06); 
    }
    
    .meta-progresso-container { 
        margin-top: 20px; 
        text-align: left; 
        background: var(--bg-global); 
        padding: 15px; 
        border-radius: 12px; 
        border: 1px solid var(--borda); 
    }
    .meta-barra-bg { 
        background: rgba(139, 92, 246, 0.1); 
        height: 10px; 
        border-radius: 6px; 
        overflow: hidden; 
        width: 100%; 
        margin-top: 8px; 
    }
    .meta-barra-fill { 
        background: linear-gradient(90deg, var(--roxo-base), #4f46e5); 
        height: 100%; 
        border-radius: 6px; 
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); 
    }

    /* ==========================================================================
       BANNER DO SIMULADO (MODO PROVA)
       ========================================================================== */
    .banner-simulado {
        background: linear-gradient(135deg, var(--bg-card) 0%, rgba(234, 88, 12, 0.05) 100%);
        border: 1px solid rgba(234, 88, 12, 0.2);
        border-radius: 24px; 
        padding: 40px 45px; 
        margin-bottom: 50px;
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        flex-wrap: wrap; 
        gap: 25px;
        box-shadow: 0 10px 30px -5px rgba(234, 88, 12, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .banner-simulado:hover { 
        transform: translateY(-4px); 
        box-shadow: 0 15px 35px -5px rgba(234, 88, 12, 0.1); 
    }
    .banner-info h3 { 
        margin: 0 0 10px 0; 
        color: var(--texto-principal); 
        font-size: 1.6rem; 
        font-weight: 800; 
        letter-spacing: -0.02em; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
    }
    .banner-info p { 
        margin: 0; 
        color: var(--texto-secundario); 
        font-size: 1.05rem; 
        max-width: 600px; 
        line-height: 1.6; 
    }
    .btn-simulado {
        background: linear-gradient(135deg, #ea580c, #c2410c); 
        color: white; 
        padding: 16px 35px; 
        font-size: 1.1rem; 
        font-weight: 800; 
        border-radius: 14px; 
        text-decoration: none; 
        box-shadow: 0 10px 25px rgba(234, 88, 12, 0.3); 
        transition: all 0.3s ease; 
        white-space: nowrap; 
        display: inline-block;
    }
    .btn-simulado:hover { 
        transform: translateY(-3px) scale(1.02); 
        box-shadow: 0 15px 35px rgba(234, 88, 12, 0.4); 
        color: white; 
    }

    /* ==========================================================================
       LAYOUT INFERIOR (Gráfico + Sugestões)
       ========================================================================== */
    .dashboard-layout { 
        display: grid; 
        grid-template-columns: 1fr 380px; 
        gap: 30px; 
        align-items: start; 
        margin-bottom: 50px; 
    }
    @media (max-width: 950px) { 
        .dashboard-layout { grid-template-columns: 1fr; } 
    }
    
    .card-principal { 
        background-color: var(--bg-card); 
        padding: 40px; 
        border-radius: 24px; 
        border: 1px solid var(--borda); 
        box-shadow: 0 4px 20px -5px rgba(0,0,0,0.02); 
        height: 100%; 
        display: flex; 
        flex-direction: column;
    }

    .coluna-lateral { 
        display: flex; 
        flex-direction: column; 
        gap: 24px; 
    }

    .card-acao { 
        transition: transform 0.3s ease, box-shadow 0.3s ease; 
    }
    .card-acao:hover { 
        transform: translateY(-3px) !important; 
        box-shadow: 0 12px 25px -5px rgba(139, 92, 246, 0.15) !important; 
    }

    .card-sugestao { 
        background: linear-gradient(145deg, rgba(217, 119, 6, 0.05), var(--bg-card)); 
        border: 1px solid rgba(217, 119, 6, 0.2); 
        padding: 40px 35px; 
        border-radius: 24px; 
        box-shadow: 0 10px 30px -5px rgba(217, 119, 6, 0.05); 
        display: flex; 
        flex-direction: column; 
    }
    
    .btn-acao { 
        display: block; 
        text-align: center; 
        background: var(--roxo-base); 
        color: white; 
        padding: 14px; 
        border-radius: 12px; 
        font-weight: 700; 
        text-decoration: none; 
        font-size: 0.95rem; 
        transition: all 0.2s ease; 
        box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2); 
    }
    .btn-acao:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.3); 
        color: white; 
    }

    /* ==========================================================================
       CONTROLES DO GRÁFICO RADAR
       ========================================================================== */
    .chart-controls { 
        display: flex; 
        gap: 5px; 
        margin-bottom: 20px; 
        background: var(--bg-global); 
        padding: 5px; 
        border-radius: 12px; 
        border: 1px solid var(--borda); 
        width: fit-content; 
    }
    .btn-chart-toggle { 
        background: transparent; 
        border: none; 
        color: var(--texto-secundario); 
        padding: 8px 16px; 
        font-weight: 600; 
        font-size: 0.9rem; 
        border-radius: 8px; 
        cursor: pointer; 
        transition: all 0.2s ease; 
    }
    .btn-chart-toggle.ativo { 
        background: var(--bg-card); 
        color: var(--roxo-base); 
        box-shadow: 0 2px 8px rgba(0,0,0,0.05); 
    }
  </style>
  
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">
  
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 34px; border-radius: 8px;" />
        Atomicamente <span class="badge-enem">ENEM</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        <?php if (function_exists('verificarSeEhAdmin') && verificarSeEhAdmin()): ?>
          <a href="admin.php" class="btn-acao" style="background: #7c3aed; padding: 8px 14px; font-size: 0.82rem; border-radius: 8px;">⚙️ Gerenciar</a>
        <?php endif; ?>

        <a href="simulado.php" style="background: rgba(234, 88, 12, 0.1); border: 1px solid rgba(234, 88, 12, 0.3); color: #ea580c; text-decoration: none; font-weight: 800; font-size: 0.85rem; padding: 8px 14px; border-radius: 10px; display: flex; align-items: center; gap: 6px;">⏱️ Modo Prova</a>
        <div style="display: flex; align-items: center; gap: 6px; background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.3); padding: 8px 14px; border-radius: 10px; font-weight: 800; color: #ea580c; font-size: 0.9rem;">🔥 <?php echo $streak_aluno; ?> Dias</div>
          
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 10px; font-weight: 600; cursor: pointer;">🛠️ Configs</button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()"><span id="btn-tema-texto">🌙 Modo Escuro</span></div>
            <div class="dropdown-item" style="opacity: 0.6; cursor: not-allowed;"><span>🔔 Notificações (Breve)</span></div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 14px; font-size: 0.88rem; border-radius: 10px; font-weight: 700; cursor: pointer;">👤 <?php echo $primeiro_nome; ?> ▼</button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <div style="padding: 10px; font-size: 0.75rem; color: var(--texto-secundario); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Minha Conta</div>
            <a href="perfil.php" class="dropdown-item">🧑‍🎓 Configurações do Perfil</a>
            <a href="progresso.php" class="dropdown-item">📈 Meu Progresso ENEM</a>
            <div class="dropdown-divisor"></div>
            <a href="logout.php" class="dropdown-item sair">🚪 Sair da Conta</a>
          </div>
        </div>

      </div>
    </div>
  </header>

  <main class="container" style="padding: 40px 20px; max-width: 1100px; margin: 0 auto;">
    
    <div class="hero-section">
      <h1 class="saudacao">Olá, <?php echo htmlspecialchars($primeiro_nome); ?>! 👋</h1>
      <p class="mensagem-motivacional">
        <?php if ($streak_aluno > 0): ?>
          Excelente! Você está com uma ofensiva de <strong><?php echo $streak_aluno; ?> dias</strong>. Continue a resolver exercícios hoje para manter a sua chama acesa.
        <?php else: ?>
          Pronto para iniciar os estudos? Resolva a sua primeira bateria de exercícios hoje para acender a sua ofensiva e ganhar as suas primeiras medalhas.
        <?php endif; ?>
      </p>
    </div>

    <div class="grid-estatisticas">
      <div class="card-estatistica">
        <span style="font-size: 2.8rem; display: block; margin-bottom: 15px;">📝</span>
        <h3 style="font-size: 2.2rem; margin: 0 0 5px 0; color: var(--texto-principal); font-weight: 800; letter-spacing: -0.03em;"><?php echo $totalRespondidas; ?></h3>
        <p style="color: var(--texto-secundario); font-size: 0.95rem; margin: 0; font-weight: 600;">Questões Resolvidas</p>
        <div class="meta-progresso-container">
          <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; color: var(--texto-secundario);">
            <span>Meta de Hoje:</span>
            <span style="color: var(--roxo-base);"><?php echo $totalHoje; ?> / <?php echo $meta_diaria; ?></span>
          </div>
          <div class="meta-barra-bg">
            <div class="meta-barra-fill" style="width: <?php echo $percentagem_meta; ?>%;"></div>
          </div>
        </div>
      </div>
      
      <div class="card-estatistica" style="display: flex; flex-direction: column; justify-content: center;">
        <span style="font-size: 2.8rem; display: block; margin-bottom: 15px;">🎯</span>
        <h3 style="font-size: 2.8rem; margin: 0 0 5px 0; color: #10b981; font-weight: 800; letter-spacing: -0.03em;"><?php echo $totalAcertos; ?></h3>
        <p style="color: var(--texto-secundario); font-size: 1rem; margin: 0; font-weight: 600;">Acertos Confirmados</p>
      </div>

      <div class="card-estatistica" style="display: flex; flex-direction: column; justify-content: center;">
        <span style="font-size: 2.8rem; display: block; margin-bottom: 15px;">⚡</span>
        <h3 style="font-size: 2.8rem; margin: 0 0 5px 0; color: var(--roxo-base); font-weight: 800; letter-spacing: -0.03em;"><?php echo $proficiencia_geral; ?>%</h3>
        <p style="color: var(--texto-secundario); font-size: 1rem; margin: 0; font-weight: 600;">Proficiência Geral</p>
      </div>
    </div>

    <div class="banner-simulado">
      <div class="banner-info">
        <h3>⏱️ Modo Prova: Sinta a pressão do ENEM</h3>
        <p>Gere um simulado customizado filtrando apenas as matérias que você precisa revisar. Ative o cronômetro, teste sua resistência e descubra seu tempo médio por questão.</p>
      </div>
      <a href="simulado.php" class="btn-simulado">⚙️ Configurar Simulado</a>
    </div>

    <div class="dashboard-layout">
      
      <div class="card-principal">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
            <div>
                <h3 style="margin: 0 0 5px 0; color: var(--texto-principal); font-weight: 800; font-size: 1.3rem; letter-spacing: -0.02em;">📊 Seu Mapeamento de Conhecimento</h3>
                <p style="margin: 0 0 20px 0; font-size: 0.9rem; color: var(--texto-secundario);">Acompanhe suas forças e fraquezas evoluindo.</p>
            </div>
            <div class="chart-controls">
                <button class="btn-chart-toggle" id="btnTopico" onclick="alterarGrafico('topico')">Área Geral</button>
                <button class="btn-chart-toggle ativo" id="btnSubtopico" onclick="alterarGrafico('subtopico')">Subtópico</button>
            </div>
        </div>
        
        <div style="flex-grow: 1; position: relative; display: flex; justify-content: center; align-items: center; min-height: 300px;">
          <canvas id="graficoProficiencia" style="max-width: 100%; max-height: 320px;"></canvas>
        </div>
      </div>

      <aside class="coluna-lateral">
        <a href="relatorios.php" class="card-acao" style="text-decoration: none; display: flex; align-items: center; gap: 18px; background: linear-gradient(135deg, rgba(139, 92, 246, 0.08), rgba(79, 70, 229, 0.03)); border: 1px solid rgba(139, 92, 246, 0.2); padding: 22px; border-radius: 24px; color: var(--texto-principal);">
            <div style="width: 55px; height: 55px; background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 1.8rem; box-shadow: 0 8px 20px rgba(139,92,246,0.3); flex-shrink: 0;">📈</div>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 4px 0; font-size: 1.15rem; font-weight: 800; color: var(--roxo-base);">Análise Avançada</h4>
                <p style="margin: 0; font-size: 0.88rem; color: var(--texto-secundario); font-weight: 500; line-height: 1.4;">Explore o superdetalhamento por tópicos.</p>
            </div>
            <div style="font-size: 1.2rem; color: var(--roxo-base); opacity: 0.6; font-weight: 800; margin-left: 5px;">➔</div>
        </a>

        <div class="card-sugestao">
          <h3 style="color: #b45309; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; margin: 0 0 25px 0; font-weight: 800; letter-spacing: -0.02em;"><span>💡</span> Sugestão Pedagógica</h3>
          <div style="flex-grow: 1;">
          <?php 
          if (!empty($frente_foco)): 
              if ($frente_foco === 'geral'): ?>
                  <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">Definiste <strong>Química Geral e Atomística</strong> como o teu foco. Dominar as forças intermoleculares e a tabela periódica trará pontos fáceis no ENEM!</p>
                  <a href="topico.php?id=modelos-atomicos" class="btn-acao" style="background: #d97706;">Avançar: Atomística</a>
              <?php elseif ($frente_foco === 'fisico'): ?>
                  <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">O teu foco atual é <strong>Físico-Química</strong>. Que tal desvendar os cálculos de Estequiometria e Termoquímica hoje?</p>
                  <a href="topico.php?id=estequiometria" class="btn-acao" style="background: #b45309;">Praticar Cálculos</a>
              <?php elseif ($frente_foco === 'organica'): ?>
                  <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">Foco em <strong>Química Orgânica</strong> detetado! Revisar as funções oxigenadas e a hibridação do carbono é essencial.</p>
                  <a href="topico.php?id=funcoes-organicas" class="btn-acao" style="background: #7c3aed;">Ver Cadeias Carbónicas</a>
              <?php elseif ($frente_foco === 'ambiental'): ?>
                  <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">Foco em <strong>Química Ambiental</strong> ativo. Domina os ciclos biogeoquímicos e chuva ácida!</p>
                  <a href="topico.php?id=quimica-ambiental" class="btn-acao" style="background: #059669;">Revisar Impactos Ambientais</a>
              <?php endif; 
          else: 
              if ($proficiencia_geral < 50): ?>
                  <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">Sua fixação em <strong>Química Geral</strong> precisa de uma base mais sólida. Recomendamos iniciar pelos conceitos fundamentais.</p>
                  <a href="topico.php?id=modelos-atomicos" class="btn-acao" style="background: #d97706;">Estudar Modelos Atómicos</a>
              <?php else: ?>
                  <p style="font-size: 1.05rem; line-height: 1.6; color: var(--texto-principal); margin: 0 0 25px 0; font-weight: 500;">Excelente progresso! Sua base está sólida. Escolha um foco no seu perfil para recomendações avançadas.</p>
              <?php endif; 
          endif; ?>
          </div>
          <a href="materias.php" class="btn-acao" style="background: transparent; border: 2px solid var(--roxo-base); color: var(--roxo-base); margin-top: 20px; box-shadow: none;">Explorar Todos os Tópicos</a>
        </div>
      </aside>

    </div>
  </main>

  <script>
    const dadosSubtopicos = {
        labels: <?php echo json_encode($labelsSub); ?>,
        scores: <?php echo json_encode($scoresSub); ?>
    };

    const dadosTopicos = {
        labels: <?php echo json_encode($labelsTop); ?>,
        scores: <?php echo json_encode($scoresTop); ?>
    };

    const darkActive = document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = darkActive ? 'rgba(255,255,255,0.08)' : '#e2e8f0';
    const angleColor = darkActive ? 'rgba(255,255,255,0.05)' : '#f1f5f9';
    const labelColor = darkActive ? '#9ca3af' : '#475569';

    const ctx = document.getElementById('graficoProficiencia').getContext('2d');
    
    let radarChart = new Chart(ctx, {
        type: 'radar',
        data: {
            labels: dadosSubtopicos.labels,
            datasets: [{
                label: 'Sua Proficiência (%)',
                data: dadosSubtopicos.scores,
                backgroundColor: 'rgba(139, 92, 246, 0.15)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 2.5,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(139, 92, 246, 1)',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    angleLines: { display: true, color: angleColor },
                    grid: { color: gridColor },
                    pointLabels: { font: { family: 'Inter', size: 12, weight: '700' }, color: labelColor },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: { display: false, stepSize: 20 }
                }
            }
        }
    });

    function alterarGrafico(tipo) {
        const btnTopico = document.getElementById('btnTopico');
        const btnSubtopico = document.getElementById('btnSubtopico');

        if (tipo === 'topico') {
            radarChart.data.labels = dadosTopicos.labels;
            radarChart.data.datasets[0].data = dadosTopicos.scores;
            btnTopico.classList.add('ativo');
            btnSubtopico.classList.remove('ativo');
        } else {
            radarChart.data.labels = dadosSubtopicos.labels;
            radarChart.data.datasets[0].data = dadosSubtopicos.scores;
            btnSubtopico.classList.add('ativo');
            btnTopico.classList.remove('ativo');
        }
        radarChart.update();
    }

    function alternarDropdown(id) {
        document.querySelectorAll('.dropdown-conteudo').forEach(drop => {
            if(drop.id !== id) drop.classList.remove('mostrar');
        });
        document.getElementById(id).classList.toggle('mostrar');
    }

    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => {
                drop.classList.remove('mostrar');
            });
        }
    }
  </script>
</body>
</html>
