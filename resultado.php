<?php
session_start();
require_once 'config.php';

// Proteção
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$primeiro_nome = explode(' ', trim($_SESSION['user_nome'] ?? 'Estudante'))[0];

// Recebendo os dados da prova
$respostas = $_POST['respostas'] ?? []; 
$tempo_gasto = (int) ($_POST['tempo_gasto'] ?? 0);
$total_questoes = (int) ($_POST['total_questoes_geradas'] ?? count($respostas));

// Contadores gerais
$acertos = 0;
$erros = 0;
$detalhes_revisao = []; 

try {
    $pdo->beginTransaction();

    foreach ($respostas as $q_id => $letra_escolhida) {
        $stmt = $pdo->prepare("SELECT id, letra, eh_correta FROM alternatives WHERE question_id = ?");
        $stmt->execute([$q_id]);
        $alts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $letra_correta = '';
        $chosen_id = null;
        $is_correct = 0;
        
        foreach ($alts as $a) {
            if ($a['eh_correta'] == 1) { $letra_correta = $a['letra']; }
            if ($a['letra'] === $letra_escolhida) { $chosen_id = $a['id']; }
        }
        
        if ($letra_escolhida === $letra_correta) {
            $acertos++;
            $is_correct = 1;
        } else {
            $erros++;
        }
        
        if ($chosen_id) {
            $stmtProg = $pdo->prepare("
                INSERT INTO user_progress (user_id, question_id, alternative_id, is_correct, foi_correta, respondido_em) 
                VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE alternative_id = ?, is_correct = ?, foi_correta = ?, respondido_em = CURRENT_TIMESTAMP
            ");
            $stmtProg->execute([$user_id, $q_id, $chosen_id, $is_correct, $is_correct, $chosen_id, $is_correct, $is_correct]);
        }
        
        $detalhes_revisao[$q_id] = [
            'escolhida' => $letra_escolhida,
            'correta' => $letra_correta,
            'is_correct' => $is_correct
        ];
    }

    $em_branco = max(0, $total_questoes - ($acertos + $erros));
    $perc_acertos = ($total_questoes > 0) ? round(($acertos / $total_questoes) * 100) : 0;
    $perc_erros = ($total_questoes > 0) ? round(($erros / $total_questoes) * 100) : 0;
    
    // Atualizar Streak (Ofensiva)
    $stmtUserStreak = $pdo->prepare("SELECT streak, ultimo_estudo FROM users WHERE id = :uid");
    $stmtUserStreak->execute([':uid' => $user_id]);
    $uData = $stmtUserStreak->fetch(PDO::FETCH_ASSOC);
    
    $hoje = date('Y-m-d');
    $ontem = date('Y-m-d', strtotime('-1 day'));
    $ultimo_estudo = $uData['ultimo_estudo'] ?? null;
    $streak_atual = $uData['streak'] ?? 0;
    
    if ($ultimo_estudo !== $hoje) {
        $novo_streak = ($ultimo_estudo === $ontem) ? $streak_atual + 1 : 1;
        $pdo->prepare("UPDATE users SET streak = :s, ultimo_estudo = :h WHERE id = :uid")
            ->execute([':s' => $novo_streak, ':h' => $hoje, ':uid' => $user_id]);
    }

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro ao salvar o progresso: " . $e->getMessage());
}

// Preparar Gabarito e Estatísticas por Subtópico
$ids_respondidos = array_keys($respostas);
$questoes_gabarito = [];
$stats_topicos = [];

if (!empty($ids_respondidos)) {
    $inQuery = implode(',', array_fill(0, count($ids_respondidos), '?'));
    
    // CORREÇÃO APLICADA AQUI: Alterado de s.nome para s.titulo
    $stmtGab = $pdo->prepare("
        SELECT q.*, s.titulo as topico_nome 
        FROM questions q 
        LEFT JOIN subtopics s ON q.subtopic_id = s.id 
        WHERE q.id IN ($inQuery) 
        ORDER BY q.id ASC
    ");
    $stmtGab->execute($ids_respondidos);
    $questoes_gabarito = $stmtGab->fetchAll(PDO::FETCH_ASSOC);

    // Agrupamento lógico dos desempenhos
    foreach ($questoes_gabarito as $q) {
        $nome_topico = $q['topico_nome'] ?? 'Assunto Geral';
        if (!isset($stats_topicos[$nome_topico])) {
            $stats_topicos[$nome_topico] = ['acertos' => 0, 'erros' => 0, 'total' => 0];
        }
        $stats_topicos[$nome_topico]['total']++;
        
        if ($detalhes_revisao[$q['id']]['is_correct']) {
            $stats_topicos[$nome_topico]['acertos']++;
        } else {
            $stats_topicos[$nome_topico]['erros']++;
        }
    }
}

function formatarTempo($segundos) {
    $m = floor($segundos / 60);
    $s = $segundos % 60;
    return sprintf("%02dm %02ds", $m, $s);
}
$tempo_medio = ($total_questoes > 0) ? round($tempo_gasto / $total_questoes) : 0;

$bound1 = $perc_acertos;
$bound2 = $perc_acertos + $perc_erros;
$conic_gradient = "conic-gradient(#10b981 0% {$bound1}%, #ef4444 {$bound1}% {$bound2}%, #4b5563 {$bound2}% 100%)";

if ($perc_acertos >= 80) $feedback = ["🏆 Desempenho de Elite!", "Aprovado com louvor! Você dominou esses tópicos."];
elseif ($perc_acertos >= 50) $feedback = ["🔥 Muito Bem!", "Você tem uma boa base, mas revisar os erros te levará ao topo."];
else $feedback = ["📚 Foco na Revisão!", "Use este resultado como um mapa do que você precisa estudar mais."];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Análise de Desempenho | Atomicamente</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); margin: 0; transition: background-color 0.3s; }
    
    .topo-dash { border-bottom: 1px solid var(--borda); background: var(--bg-card); position: sticky; top: 0; z-index: 100; }
    .nav-dash { padding: 12px 20px; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; }
    
    .logo-area { display: flex; align-items: center; gap: 20px; }
    .marca-dash { font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--texto-principal); letter-spacing: -0.03em; }
    .btn-voltar-header { font-size: 0.85rem; font-weight: 700; color: var(--texto-secundario); text-decoration: none; padding: 6px 12px; border: 1px solid var(--borda); border-radius: 8px; transition: all 0.2s; background: var(--bg-global); }
    .btn-voltar-header:hover { color: var(--roxo-base); border-color: var(--roxo-base); background: rgba(139, 92, 246, 0.05); transform: translateX(-3px); }

    .container-resultado { max-width: 1100px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 30px; align-items: start; }
    @media (max-width: 900px) { .container-resultado { grid-template-columns: 1fr; } }

    /* CARDS LATERAIS */
    .card-painel { background: var(--bg-card); border-radius: 24px; border: 1px solid var(--borda); padding: 30px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.03); margin-bottom: 25px; }
    
    .chart-container { display: flex; justify-content: center; align-items: center; position: relative; margin: 20px 0 30px 0; }
    .doughnut { width: 180px; height: 180px; border-radius: 50%; background: <?php echo $conic_gradient; ?>; display: flex; justify-content: center; align-items: center; box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .doughnut-hole { width: 130px; height: 130px; border-radius: 50%; background: var(--bg-card); display: flex; flex-direction: column; justify-content: center; align-items: center; }
    .nota-final { font-size: 2.2rem; font-weight: 800; color: var(--texto-principal); line-height: 1; margin-bottom: 5px;}
    .nota-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: var(--texto-secundario); }

    .stat-linha { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--borda); font-weight: 600; font-size: 0.95rem; }
    .stat-linha:last-child { border-bottom: none; }
    .badge-stat { padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 0.9rem; }
    .bg-verde { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .bg-vermelho { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .bg-cinza { background: rgba(107, 114, 128, 0.1); color: var(--texto-secundario); }

    /* BARRA DE PROGRESSO POR TÓPICO */
    .topico-linha { margin-bottom: 18px; }
    .topico-header { display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; color: var(--texto-principal); }
    .topico-bar-container { width: 100%; background: rgba(239, 68, 68, 0.15); height: 8px; border-radius: 4px; overflow: hidden; display: flex; }
    .topico-bar-fill { background: #10b981; height: 100%; border-radius: 4px; transition: width 1s ease-out; }

    .btn-acao { display: block; width: 100%; text-align: center; text-decoration: none; padding: 16px; border-radius: 14px; font-weight: 800; font-size: 1.05rem; transition: all 0.2s; margin-top: 15px; }
    .btn-primario { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3); }
    .btn-primario:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(139, 92, 246, 0.4); }
    .btn-secundario { background: transparent; color: var(--texto-secundario); border: 2px solid var(--borda); }
    .btn-secundario:hover { background: var(--borda); color: var(--texto-principal); }

    /* CORPO DIREITO */
    .hero-feedback { background: linear-gradient(135deg, var(--roxo-suave), rgba(139, 92, 246, 0.05)); border: 1px solid rgba(139, 92, 246, 0.2); border-radius: 20px; padding: 30px; margin-bottom: 30px; }
    .hero-feedback h1 { margin: 0 0 10px 0; color: var(--roxo-base); font-size: 1.8rem; font-weight: 800; }
    .hero-feedback p { margin: 0; color: var(--texto-principal); font-size: 1.1rem; line-height: 1.5; font-weight: 500; }

    .grid-tempo { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 40px; }
    .card-tempo { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 16px; padding: 20px; display: flex; align-items: center; gap: 15px; }
    .icone-tempo { width: 50px; height: 50px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; justify-content: center; align-items: center; font-size: 1.5rem; }
    .info-tempo h4 { margin: 0; font-size: 0.8rem; text-transform: uppercase; color: var(--texto-secundario); letter-spacing: 0.05em; font-weight: 700; }
    .info-tempo span { font-size: 1.4rem; font-weight: 800; color: var(--texto-principal); }

    .titulo-gabarito { font-size: 1.4rem; font-weight: 800; margin-bottom: 25px; color: var(--texto-principal); border-bottom: 2px solid var(--borda); padding-bottom: 15px; display: flex; align-items: center; gap: 10px;}

    .questao-revisao { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 20px; padding: 30px; margin-bottom: 20px; transition: border-color 0.2s; }
    .questao-revisao:hover { border-color: var(--roxo-base); }
    .qr-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-weight: 800; font-size: 0.9rem; }
    .qr-badge { padding: 4px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
    .qr-badge.certo { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .qr-badge.errado { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .qr-topico { color: var(--texto-secundario); background: var(--bg-global); padding: 4px 10px; border-radius: 8px; border: 1px solid var(--borda); }
    
    .qr-enunciado { font-size: 1.05rem; line-height: 1.6; font-weight: 500; color: var(--texto-principal); margin-bottom: 25px; }

    .qr-alt { display: flex; align-items: center; gap: 15px; padding: 12px 15px; border-radius: 12px; margin-bottom: 8px; border: 1px solid transparent; background: var(--bg-global); }
    .qr-alt .letra { font-weight: 800; width: 30px; height: 30px; display: flex; justify-content: center; align-items: center; border-radius: 50%; background: var(--bg-card); color: var(--texto-secundario); border: 2px solid var(--borda); }
    .qr-alt .texto { font-size: 0.95rem; color: var(--texto-principal); flex: 1; }
    
    .qr-alt.is-correct { background: rgba(16, 185, 129, 0.05); border-color: #10b981; }
    .qr-alt.is-correct .letra { background: #10b981; color: white; border-color: #10b981; }
    .qr-alt.is-correct .texto { font-weight: 700; color: #065f46; }

    .qr-alt.is-wrong-choice { background: rgba(239, 68, 68, 0.05); border-color: #ef4444; }
    .qr-alt.is-wrong-choice .letra { background: #ef4444; color: white; border-color: #ef4444; }
    .qr-alt.is-wrong-choice .texto { font-weight: 700; color: #991b1b; text-decoration: line-through; opacity: 0.8; }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="nav-dash">
      <div class="logo-area">
          <a href="dashboard.php" class="marca-dash">
            <img src="assets/icone-simplificado.png" alt="Logo" style="height: 34px; border-radius: 8px;" />
            Atomicamente
          </a>
          <a href="dashboard.php" class="btn-voltar-header">⬅️ Voltar ao Painel</a>
      </div>
      <div class="menu-dropdown">
        <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 10px; font-weight: 600; cursor: pointer;">🛠️ Modo</button>
        <div id="drop-config" class="dropdown-conteudo">
          <div class="dropdown-item" onclick="alternarModoNoturno()"><span id="btn-tema-texto">🌙 Modo Escuro</span></div>
        </div>
      </div>
    </div>
  </header>

  <div class="container-resultado">
    
    <aside>
      <div class="card-painel" style="text-align: center;">
        <h2 style="margin: 0 0 5px 0; font-size: 1.3rem; font-weight: 800;">Raio-X da Prova</h2>
        <p style="margin: 0; color: var(--texto-secundario); font-size: 0.9rem;">Seu desempenho em números</p>
        
        <div class="chart-container">
          <div class="doughnut">
            <div class="doughnut-hole">
              <span class="nota-final"><?php echo $perc_acertos; ?>%</span>
              <span class="nota-label">Nota Final</span>
            </div>
          </div>
        </div>

        <div class="stat-linha">
          <span>✅ Respostas Corretas</span>
          <span class="badge-stat bg-verde"><?php echo $acertos; ?></span>
        </div>
        <div class="stat-linha">
          <span>❌ Respostas Incorretas</span>
          <span class="badge-stat bg-vermelho"><?php echo $erros; ?></span>
        </div>
        <div class="stat-linha" style="border-bottom: none;">
          <span>⚪ Deixadas em Branco</span>
          <span class="badge-stat bg-cinza"><?php echo $em_branco; ?></span>
        </div>
      </div>

      <?php if (!empty($stats_topicos)): ?>
      <div class="card-painel">
        <h3 style="margin: 0 0 20px 0; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
            🎯 Análise por Assunto
        </h3>
        
        <?php foreach ($stats_topicos as $nome => $dados): 
            $perc = round(($dados['acertos'] / $dados['total']) * 100);
        ?>
            <div class="topico-linha">
                <div class="topico-header">
                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 65%;" title="<?php echo htmlspecialchars($nome); ?>">
                        <?php echo htmlspecialchars($nome); ?>
                    </span>
                    <span style="color: var(--texto-secundario); font-weight: 800;">
                        <?php echo $perc; ?>% <span style="font-size: 0.75rem; font-weight: 600;">(<?php echo $dados['acertos']; ?>/<?php echo $dados['total']; ?>)</span>
                    </span>
                </div>
                <div class="topico-bar-container">
                    <div class="topico-bar-fill" style="width: <?php echo $perc; ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <a href="dashboard.php" class="btn-acao btn-primario">🏠 Voltar ao Painel Inicial</a>
      <a href="simulado.php" class="btn-acao btn-secundario">🔄 Gerar Novo Simulado</a>
    </aside>

    <main>
      <div class="hero-feedback">
        <h1><?php echo $primeiro_nome; ?>, <?php echo $feedback[0]; ?></h1>
        <p><?php echo $feedback[1]; ?></p>
      </div>

      <div class="grid-tempo">
        <div class="card-tempo">
          <div class="icone-tempo">⏱️</div>
          <div class="info-tempo">
            <h4>Tempo Total Gasto</h4>
            <span><?php echo formatarTempo($tempo_gasto); ?></span>
          </div>
        </div>
        <div class="card-tempo">
          <div class="icone-tempo" style="color: #10b981; background: rgba(16, 185, 129, 0.1);">⚡</div>
          <div class="info-tempo">
            <h4>Ritmo Médio</h4>
            <span><?php echo formatarTempo($tempo_medio); ?> / questão</span>
          </div>
        </div>
      </div>

      <h2 class="titulo-gabarito">📋 Revisão Detalhada (Gabarito)</h2>

      <?php if ($em_branco > 0): ?>
        <div style="background: rgba(107, 114, 128, 0.1); border: 1px solid rgba(107, 114, 128, 0.2); padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; color: var(--texto-secundario); font-size: 0.95rem; font-weight: 500;">
          💡 <strong>Nota:</strong> Você deixou <?php echo $em_branco; ?> questão(ões) em branco. Elas foram omitidas desta revisão pois não houve resposta para correção.
        </div>
      <?php endif; ?>

      <?php if (empty($questoes_gabarito) && $em_branco == $total_questoes): ?>
        <div style="text-align: center; padding: 50px 0; color: var(--texto-secundario);">
          <h2>Você entregou a prova inteira em branco! 😴</h2>
          <p>Nenhum gabarito para analisar desta vez.</p>
        </div>
      <?php endif; ?>

      <?php $q_num = 1; foreach ($questoes_gabarito as $q): ?>
        <?php 
          $dados_rev = $detalhes_revisao[$q['id']];
          $acertou = $dados_rev['is_correct'];
          
          $stmtAlt = $pdo->prepare("SELECT * FROM alternatives WHERE question_id = ? ORDER BY letra ASC");
          $stmtAlt->execute([$q['id']]);
          $alts = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="questao-revisao">
          <div class="qr-header">
            <span class="qr-badge <?php echo $acertou ? 'certo' : 'errado'; ?>">
              <?php echo $acertou ? '✅ Você Acertou' : '❌ Você Errou'; ?>
            </span>
            <span class="qr-topico"><?php echo htmlspecialchars($q['topico_nome']); ?></span>
          </div>

          <div class="qr-enunciado">
            <?php echo nl2br(htmlspecialchars($q['enunciado'])); ?>
          </div>

          <div class="qr-alternativas">
            <?php foreach ($alts as $alt): ?>
              <?php 
                $eh_gabarito = ($alt['letra'] === $dados_rev['correta']);
                $foi_escolhida = ($alt['letra'] === $dados_rev['escolhida']);
                
                $classe_alt = '';
                if ($eh_gabarito) $classe_alt = 'is-correct';
                elseif ($foi_escolhida && !$eh_gabarito) $classe_alt = 'is-wrong-choice';
              ?>
              
              <div class="qr-alt <?php echo $classe_alt; ?>">
                <div class="letra"><?php echo $alt['letra']; ?></div>
                <div class="texto">
                  <?php echo htmlspecialchars($alt['texto_alternativa']); ?>
                  <?php if ($eh_gabarito): ?> <span style="margin-left: 10px; font-size: 0.8rem; background: #10b981; color: white; padding: 2px 8px; border-radius: 6px;">GABARITO</span> <?php endif; ?>
                  <?php if ($foi_escolhida && !$eh_gabarito): ?> <span style="margin-left: 10px; font-size: 0.8rem; background: #ef4444; color: white; padding: 2px 8px; border-radius: 6px;">SUA RESPOSTA</span> <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php $q_num++; endforeach; ?>

    </main>
  </div>

  <script>
    function alternarDropdown(id) {
        document.querySelectorAll('.dropdown-conteudo').forEach(drop => { if(drop.id !== id) drop.classList.remove('mostrar'); });
        document.getElementById(id).classList.toggle('mostrar');
    }
    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => drop.classList.remove('mostrar'));
        }
    }
  </script>

  <?php if ($perc_acertos >= 70): ?>
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <script>
    window.addEventListener('load', function() {
        var duration = 3 * 1000;
        var end = Date.now() + duration;

        (function frame() {
            confetti({ particleCount: 5, angle: 60, spread: 55, origin: { x: 0 }, colors: ['#8b5cf6', '#10b981', '#f59e0b'] });
            confetti({ particleCount: 5, angle: 120, spread: 55, origin: { x: 1 }, colors: ['#8b5cf6', '#10b981', '#f59e0b'] });
            if (Date.now() < end) { requestAnimationFrame(frame); }
        }());
    });
  </script>
  <?php endif; ?>
</body>
</html>
