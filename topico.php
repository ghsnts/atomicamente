<?php
session_start();
require_once 'config.php';

// 1. Proteção: Garante que o aluno está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$slug_atual = isset($_GET['id']) ? $_GET['id'] : 'modelos-atomicos';

try {
    // =========================================================================
    // DADOS DO USUÁRIO, META DIÁRIA & FOCO PEDAGÓGICO
    // =========================================================================
    $stmtUser = $pdo->prepare("SELECT meta_diaria, frente_foco FROM users WHERE id = :uid");
    $stmtUser->execute([':uid' => $user_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    $meta_diaria = $userData['meta_diaria'] ?? 0;
    $foco_aluno = $userData['frente_foco'] ?? '';

    // Contar exercícios feitos hoje
    $stmtHoje = $pdo->prepare("SELECT COUNT(*) FROM user_progress WHERE user_id = :uid AND DATE(respondido_em) = CURDATE()");
    $stmtHoje->execute([':uid' => $user_id]);
    $feitos_hoje = $stmtHoje->fetchColumn();

    // =========================================================================
    // MOTOR DA BARRA LATERAL: Sincronização, Progresso e Tag de Foco
    // =========================================================================
    $frentes_sidebar = [];
    $stmtFrentes = $pdo->query("SELECT * FROM frentes ORDER BY id ASC");
    
    while ($frente = $stmtFrentes->fetch()) {
        
        // Verifica se a frente atual é o foco do aluno
        $nome_frente = $frente['nome'];
        $eh_foco = false;
        if (
            ($foco_aluno === 'geral' && stripos($nome_frente, 'geral') !== false) ||
            ($foco_aluno === 'fisico' && stripos($nome_frente, 'físico') !== false) ||
            ($foco_aluno === 'fisico' && stripos($nome_frente, 'fisico') !== false) ||
            ($foco_aluno === 'organica' && stripos($nome_frente, 'orgânica') !== false) ||
            ($foco_aluno === 'organica' && stripos($nome_frente, 'organica') !== false) ||
            ($foco_aluno === 'ambiental' && stripos($nome_frente, 'ambiental') !== false)
        ) {
            $eh_foco = true;
        }
        $frente['eh_foco'] = $eh_foco;

        $stmtTopicos = $pdo->prepare("
            SELECT t.*,
                (SELECT COUNT(*) FROM questions q WHERE q.subtopic_id = t.id) as total_questoes,
                (SELECT COUNT(DISTINCT up.question_id) 
                 FROM user_progress up 
                 JOIN questions q ON up.question_id = q.id 
                 WHERE q.subtopic_id = t.id AND up.user_id = :uid) as respondidas
            FROM topicos t 
            WHERE t.frente_id = :fid 
            ORDER BY t.id ASC
        ");
        $stmtTopicos->execute([':uid' => $user_id, ':fid' => $frente['id']]);
        $topicos_lista = $stmtTopicos->fetchAll();
        
        foreach ($topicos_lista as &$t) {
            $t['porcentagem'] = ($t['total_questoes'] > 0) ? round(($t['respondidas'] / $t['total_questoes']) * 100) : 0;
        }
        
        $frente['topicos'] = $topicos_lista;
        $frentes_sidebar[] = $frente;
    }

    // =========================================================================
    // CARREGAR OS DADOS DO TÓPICO ATUAL SELECIONADO
    // =========================================================================
    $stmtAtual = $pdo->prepare("SELECT * FROM topicos WHERE slug = :slug");
    $stmtAtual->execute([':slug' => $slug_atual]);
    $topico_dados = $stmtAtual->fetch();

    if (!$topico_dados) {
        die("Tópico não encontrado no mapeamento da grade.");
    }

    $topico_id = $topico_dados['id'];
    $nome_topico_atual = $topico_dados['nome'];

    $stmtAulas = $pdo->prepare("SELECT * FROM aulas WHERE topico_id = :tid ORDER BY ordem ASC");
    $stmtAulas->execute([':tid' => $topico_id]);
    $aulas_topico = $stmtAulas->fetchAll();

    $stmtQuestoes = $pdo->prepare("
        SELECT q.*, up.is_correct as ja_respondida 
        FROM questions q 
        LEFT JOIN user_progress up ON q.id = up.question_id AND up.user_id = :uid
        WHERE q.subtopic_id = :tid 
        ORDER BY q.id ASC
    ");
    $stmtQuestoes->execute([':uid' => $user_id, ':tid' => $topico_id]);
    $questoes_topico = $stmtQuestoes->fetchAll();

} catch (PDOException $e) {
    die("Erro de conexão na sala de aula: " . $e->getMessage());
}

// Lógica de Processamento de Resposta e Ofensiva (Streak)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $question_id = $_POST['question_id'];
    $alternativa_escolhida = $_POST['alternative_letter'];

    // Verifica a correta
    $stmtCheck = $pdo->prepare("SELECT letter FROM alternatives WHERE question_id = :qid AND is_correct = 1");
    $stmtCheck->execute([':qid' => $question_id]);
    $correta = $stmtCheck->fetchColumn();

    $is_correct = ($alternativa_escolhida === $correta) ? 1 : 0;

    // Salva a resposta
    $stmtProg = $pdo->prepare("
        INSERT INTO user_progress (user_id, question_id, is_correct, respondido_em) 
        VALUES (:uid, :qid, :isc, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE is_correct = :isc, respondido_em = CURRENT_TIMESTAMP
    ");
    $stmtProg->execute([':uid' => $user_id, ':qid' => $question_id, ':isc' => $is_correct]);

    // =========================================================================
    // MOTOR DA OFENSIVA (STREAK 🔥)
    // =========================================================================
    $stmtUserStreak = $pdo->prepare("SELECT streak, ultimo_estudo FROM users WHERE id = :uid");
    $stmtUserStreak->execute([':uid' => $user_id]);
    $uData = $stmtUserStreak->fetch(PDO::FETCH_ASSOC);
    
    $hoje = date('Y-m-d');
    $ontem = date('Y-m-d', strtotime('-1 day'));
    $ultimo_estudo = $uData['ultimo_estudo'] ?? null;
    $streak_atual = $uData['streak'] ?? 0;

    if ($ultimo_estudo !== $hoje) {
        if ($ultimo_estudo === $ontem) {
            $novo_streak = $streak_atual + 1; // Estudou ontem e hoje: Aumenta o combo!
        } else {
            $novo_streak = 1; // Ficou dias sem estudar: Recomeça do 1
        }
        $pdo->prepare("UPDATE users SET streak = :s, ultimo_estudo = :h WHERE id = :uid")
            ->execute([':s' => $novo_streak, ':h' => $hoje, ':uid' => $user_id]);
    }

    header("Location: topico.php?id=" . $slug_atual . "#questao-" . $question_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($nome_topico_atual); ?> | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    body { font-family: 'Inter', sans-serif; }
    
    .layout-sala-aula { display: grid; grid-template-columns: 320px 1fr; min-height: calc(100vh - 65px); }
    
    /* SCROLLBAR CUSTOMIZADA PARA A SIDEBAR */
    .sidebar-grade::-webkit-scrollbar { width: 6px; }
    .sidebar-grade::-webkit-scrollbar-track { background: transparent; }
    .sidebar-grade::-webkit-scrollbar-thumb { background-color: var(--borda); border-radius: 10px; }
    
    .sidebar-grade { 
      background-color: var(--bg-card); 
      border-right: 1px solid var(--borda); 
      padding: 30px 20px; 
      overflow-y: auto; 
      max-height: calc(100vh - 65px); 
    }
    
    .titulo-categoria { 
      display: flex; justify-content: space-between; align-items: center;
      font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; 
      color: var(--texto-secundario); margin: 25px 0 12px 0; font-weight: 800; 
    }
    
    /* TAG DO FOCO NA SIDEBAR */
    .badge-foco-sidebar {
      background: var(--roxo-base); color: white; font-size: 0.65rem; font-weight: 800;
      padding: 3px 8px; border-radius: 6px; letter-spacing: 0; box-shadow: 0 2px 4px rgba(109, 40, 217, 0.2);
    }
    
    .link-subtopico-wrapper { 
      display: flex; justify-content: space-between; align-items: center; 
      padding: 12px 14px; border-radius: 10px; margin-bottom: 6px; 
      text-decoration: none; color: var(--texto-principal); 
      transition: all 0.2s ease; border: 1px solid transparent;
    }
    .link-subtopico-wrapper:hover { 
      background: var(--roxo-suave); color: var(--roxo-base); transform: translateX(3px);
    }
    .link-subtopico-wrapper.ativo { 
      background: var(--roxo-suave); color: var(--roxo-base); font-weight: 700; 
      border-color: rgba(139, 92, 246, 0.2);
      border-left: 4px solid var(--roxo-base); padding-left: 10px;
    }
    .label-titulo { font-size: 0.9rem; max-width: 190px; line-height: 1.3; }
    
    .badge-progresso { 
      font-size: 0.75rem; font-weight: 800; padding: 3px 8px; border-radius: 6px; 
      background-color: var(--bg-global); color: var(--texto-secundario); transition: all 0.2s; 
    }
    .link-subtopico-wrapper.ativo .badge-progresso, .link-subtopico-wrapper:hover .badge-progresso { 
      background-color: white; color: var(--roxo-base); box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    /* ÁREA CENTRAL - SALA DE AULA */
    .centro-aula { 
      padding: 40px 60px; background-color: var(--bg-global); overflow-y: auto; max-height: calc(100vh - 65px); scroll-behavior: smooth;
    }
    .centro-aula::-webkit-scrollbar { width: 8px; }
    .centro-aula::-webkit-scrollbar-track { background: transparent; }
    .centro-aula::-webkit-scrollbar-thumb { background-color: var(--borda); border-radius: 10px; }

    .card-modulo { 
      background-color: var(--bg-card); border-radius: 16px; border: 1px solid var(--borda); 
      padding: 35px; margin-bottom: 30px; color: var(--texto-principal); 
      box-shadow: 0 4px 15px -3px rgba(0,0,0,0.02); position: relative; transition: box-shadow 0.3s ease;
    }
    .card-modulo:hover { box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    
    .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 14px; margin: 20px 0; background: #000; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
    
    /* OPÇÕES DE EXERCÍCIO REDESENHADAS */
    .opcao-exercicio { 
      display: flex; align-items: center; gap: 15px; padding: 16px; 
      border: 2px solid var(--borda); border-radius: 12px; margin-bottom: 12px; 
      cursor: pointer; transition: all 0.2s ease-in-out; background-color: var(--bg-card); 
    }
    .opcao-exercicio:hover:not(.respondida) { 
      border-color: var(--roxo-base); background-color: var(--roxo-suave); transform: translateY(-2px);
    }
    .opcao-exercicio input[type="radio"] {
      width: 18px; height: 18px; accent-color: var(--roxo-base); cursor: pointer; margin: 0;
    }
    
    /* WIDGET DA META DIÁRIA (PREMIUM) */
    .widget-meta { 
      background: linear-gradient(135deg, var(--roxo-base) 0%, #4f46e5 100%); 
      color: white; padding: 25px; border-radius: 16px; margin-bottom: 40px; 
      display: flex; align-items: center; justify-content: space-between; gap: 20px;
      box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4); border: 1px solid rgba(255,255,255,0.1);
    }
    .barra-meta-bg { background: rgba(255, 255, 255, 0.2); height: 8px; border-radius: 10px; width: 100%; min-width: 250px; overflow: hidden; margin-top: 10px; }
    .barra-meta-progresso { background: #50fa7b; height: 100%; border-radius: 10px; transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 0 10px rgba(80, 250, 123, 0.5); }

    /* BADGES DE STATUS (Histórico da Questão) */
    .status-questao { display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 700; padding: 6px 14px; border-radius: 20px; margin-bottom: 15px; letter-spacing: 0.02em; }
    .status-acertou { background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
    .status-errou { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash" style="border-bottom: 1px solid var(--borda);">
    <div class="container nav-dash" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      
      <a href="dashboard.php" class="marca-dash" style="font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.03em; display: flex; align-items: center; gap: 10px;">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 34px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);" />
        Atomicamente 
        <span class="badge-enem" style="background: var(--roxo-base); font-size: 0.65rem; padding: 4px 8px; transform: translateY(-1px);">SALA DE AULA</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 18px;">
        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: opacity 0.2s;">Painel Inicial</a>
        
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 14px; font-size: 0.88rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
            🛠️ Modo
          </button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()"><span id="btn-tema-texto">🌙 Escuro</span></div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 16px; font-size: 0.9rem; border-radius: 10px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 6px rgba(139, 92, 246, 0.2); transition: all 0.2s;">
            👤 <?php echo explode(' ', $_SESSION['user_nome'] ?? 'Estudante')[0]; ?> <span style="font-size: 0.6rem; margin-left: 4px;">▼</span>
          </button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <a href="perfil.php" class="dropdown-item">🧑‍🎓 Configurações do Perfil</a>
            <div class="dropdown-divisor"></div>
            <a href="logout.php" class="dropdown-item sair">🚪 Encerrar Sessão</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="layout-sala-aula">
    
    <nav class="sidebar-grade">
      <h2 style="font-size: 1.15rem; color: var(--texto-principal); margin-top:0; font-weight:800; letter-spacing: -0.02em;">Grade Temática</h2>
      
      <?php foreach ($frentes_sidebar as $frente): ?>
        <div class="titulo-categoria">
          <?php echo htmlspecialchars($frente['nome']); ?>
          <?php if ($frente['eh_foco']): ?>
            <span class="badge-foco-sidebar">⭐ FOCO</span>
          <?php endif; ?>
        </div>
        
        <?php foreach ($frente['topicos'] as $topico): ?>
          <a href="topico.php?id=<?php echo htmlspecialchars($topico['slug']); ?>" class="link-subtopico-wrapper <?php echo ($topico['slug'] === $slug_atual) ? 'ativo' : ''; ?>">
             <span class="label-titulo"><?php echo htmlspecialchars($topico['nome']); ?></span>
             <span class="badge-progresso"><?php echo $topico['porcentagem']; ?>%</span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <main class="centro-aula">
      
      <?php if ($meta_diaria > 0): ?>
        <?php $porcentagem_meta = min(round(($feitos_hoje / $meta_diaria) * 100), 100); ?>
        <div class="widget-meta">
          <div>
            <h4 style="margin: 0 0 6px 0; font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em;">Foco Diário de Estudos 🎯</h4>
            <p style="margin: 0; opacity: 0.95; font-size: 0.95rem;">Você resolveu <b style="color: #50fa7b;"><?php echo $feitos_hoje; ?></b> de <b><?php echo $meta_diaria; ?></b> exercícios hoje.</p>
          </div>
          <div style="text-align: right; width: 40%; max-width: 300px;">
            <span style="font-weight: 800; font-size: 1.4rem;"><?php echo $porcentagem_meta; ?>%</span>
            <div class="barra-meta-bg">
              <div class="barra-meta-progresso" style="width: <?php echo $porcentagem_meta; ?>%;"></div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div style="margin-bottom: 40px;">
        <h1 style="margin: 0; font-size: 2.2rem; color: var(--texto-principal); font-weight: 800; letter-spacing: -0.03em;"><?php echo htmlspecialchars($nome_topico_atual); ?></h1>
        <p style="margin: 8px 0 0 0; color: var(--texto-secundario); font-size: 1.05rem;">Estude a base teórica através dos resumos em vídeo e consolide com os exercícios padrão ENEM abaixo.</p>
      </div>

      <h2 style="color: var(--texto-principal); font-size: 1.4rem; font-weight: 800; margin-bottom: 20px; letter-spacing: -0.02em;">📖 Subtópicos & Aulas Teóricas</h2>
      
      <?php if (empty($aulas_topico)): ?>
        <div class="card-modulo" style="color: var(--texto-secundario); font-style: italic;">
          Material de apoio ainda não disponibilizado pela equipe pedagógica. Vá direto para a bateria de exercícios.
        </div>
      <?php else: ?>
        <?php foreach ($aulas_topico as $aula): ?>
          <div class="card-modulo">
            <h3 style="margin-top: 0; margin-bottom: 5px; color: var(--roxo-base); font-weight: 800; font-size: 1.2rem;"><?php echo htmlspecialchars($aula['titulo']); ?></h3>
            
            <?php if (!empty($aula['video_url'])): ?>
              <div class="video-container">
                <iframe src="<?php echo htmlspecialchars($aula['video_url']); ?>" allowfullscreen></iframe>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($aula['resumo'])): ?>
              <div style="line-height: 1.7; color: var(--texto-principal); font-size: 1rem; margin-top: 20px;">
                <?php echo nl2br(htmlspecialchars($aula['resumo'])); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <h2 style="color: var(--texto-principal); font-size: 1.4rem; font-weight: 800; margin: 50px 0 20px 0; letter-spacing: -0.02em;">📝 Banco de Exercícios Integrados</h2>
      
      <?php if (empty($questoes_topico)): ?>
        <div class="card-modulo" style="color: var(--texto-secundario);">
          Não existem exercícios cadastrados para este tópico no momento.
        </div>
      <?php else: ?>
        <?php $contador = 1; ?>
        <?php foreach ($questoes_topico as $questao): ?>
          <?php 
            $ja_respondida = $questao['ja_respondida'] !== null;
            $acertou = $questao['ja_respondida'] == 1;
          ?>
          
          <div class="card-modulo" id="questao-<?php echo $questao['id']; ?>">
            
            <?php if ($ja_respondida): ?>
              <div class="status-questao <?php echo $acertou ? 'status-acertou' : 'status-errou'; ?>">
                <?php echo $acertou ? '✅ Registramos o seu acerto nesta questão' : '❌ Infelizmente, você não acertou esta questão'; ?>
              </div>
            <?php endif; ?>

            <div style="margin-top: <?php echo $ja_respondida ? '5px' : '0'; ?>; margin-bottom: 20px;">
                <span style="font-size: 0.8rem; text-transform: uppercase; font-weight: 800; color: var(--roxo-base); letter-spacing: 0.05em; background: var(--roxo-suave); padding: 4px 10px; border-radius: 6px;">Questão <?php echo $contador++; ?></span>
            </div>

            <p style="font-size: 1.1rem; line-height: 1.7; color: var(--texto-principal); font-weight: 500; margin: 15px 0 25px 0;">
              <?php echo nl2br(htmlspecialchars($questao['statement'])); ?>
            </p>

            <?php 
              $stmtAlt = $pdo->prepare("SELECT * FROM alternatives WHERE question_id = :qid ORDER BY letter ASC");
              $stmtAlt->execute([':qid' => $questao['id']]);
              $alternativas = $stmtAlt->fetchAll();
            ?>

            <form action="" method="POST">
              <input type="hidden" name="question_id" value="<?php echo $questao['id']; ?>">
              <input type="hidden" name="submit_answer" value="1">

              <?php foreach ($alternativas as $alt): ?>
                <?php 
                  $destacar_correta = $ja_respondida && $alt['is_correct'] == 1;
                ?>
                <label class="opcao-exercicio <?php echo $ja_respondida ? 'respondida' : ''; ?>" style="<?php echo $destacar_correta ? 'border-color: #10b981; background: rgba(16,185,129,0.05);' : ''; ?>">
                  
                  <input type="radio" name="alternative_letter" value="<?php echo htmlspecialchars($alt['letter']); ?>" 
                         <?php echo $ja_respondida ? 'disabled' : ''; ?> required>
                  
                  <span style="font-weight: 800; font-size: 1.1rem; color: <?php echo $destacar_correta ? '#10b981' : 'var(--roxo-base)'; ?>;">
                    <?php echo htmlspecialchars($alt['letter']); ?>)
                  </span>
                  
                  <span style="color: var(--texto-principal); font-size: 1rem; flex-grow: 1;">
                    <?php echo htmlspecialchars($alt['text_content']); ?>
                  </span>

                  <?php if ($destacar_correta): ?> 
                    <span style="background: #10b981; color: white; font-size: 0.75rem; font-weight: 700; padding: 4px 10px; border-radius: 12px; letter-spacing: 0.05em;">GABARITO OFICIAL</span>
                  <?php endif; ?>
                </label>
              <?php endforeach; ?>

              <?php if (!$ja_respondida): ?>
                <button type="submit" class="btn-acao" style="margin-top: 20px; display: inline-block; width: auto; padding: 14px 35px; font-size: 1rem; font-weight: 700; border-radius: 12px; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">Confirmar Resposta</button>
              <?php endif; ?>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </main>
  </div>

  <script>
    // Configurações e Perfil Dropdown
    function alternarDropdown(id) {
        document.querySelectorAll('.dropdown-conteudo').forEach(drop => {
            if(drop.id !== id) drop.classList.remove('mostrar');
        });
        document.getElementById(id).classList.toggle('mostrar');
    }
    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => drop.classList.remove('mostrar'));
        }
    }
  </script>
</body>
</html>
