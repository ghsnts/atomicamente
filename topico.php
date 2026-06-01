<?php
session_start();
require_once 'config.php';

// 1. Proteção: Garante que o aluno está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. Identificar qual tópico/matéria o aluno está a aceder
$slug_atual = isset($_GET['id']) ? $_GET['id'] : 'modelos-atomicos';

try {
    // =========================================================================
    // MOTOR DA BARRA LATERAL: Sincronização e Cálculo de Progresso (0% a 100%)
    // =========================================================================
    $frentes_sidebar = [];
    $stmtFrentes = $pdo->query("SELECT * FROM frentes ORDER BY id ASC");
    
    while ($frente = $stmtFrentes->fetch()) {
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

    // Puxar os Subtópicos / Aulas dinâmicas deste tópico criadas pelas Admins
    $stmtAulas = $pdo->prepare("SELECT * FROM aulas WHERE topico_id = :tid ORDER BY ordem ASC");
    $stmtAulas->execute([':tid' => $topico_id]);
    $aulas_topico = $stmtAulas->fetchAll();

    // Puxar as Questões deste tópico
    $stmtQuestoes = $pdo->prepare("SELECT * FROM questions WHERE subtopic_id = :tid ORDER BY id ASC");
    $stmtQuestoes->execute([':tid' => $topico_id]);
    $questoes_topico = $stmtQuestoes->fetchAll();

} catch (PDOException $e) {
    die("Erro de sincronização na sala de aula: " . $e->getMessage());
}

// Lógica de Processamento de Resposta do Exercício (Mantendo o teu sistema)
$feedback_exercicio = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    $question_id = $_POST['question_id'];
    $alternativa_escolhida = $_POST['alternative_letter'];

    // Verificar se está correta
    $stmtCheck = $pdo->prepare("SELECT letter FROM alternatives WHERE question_id = :qid AND is_correct = 1");
    $stmtCheck->execute([':qid' => $question_id]);
    $correta = $stmtCheck->fetchColumn();

    $is_correct = ($alternativa_escolhida === $correta) ? 1 : 0;

    // Registar o progresso do aluno para recalcular os 100% na hora
    $stmtProg = $pdo->prepare("INSERT IGNORE INTO user_progress (user_id, question_id, is_correct) VALUES (:uid, :qid, :isc)");
    $stmtProg->execute([':uid' => $user_id, ':qid' => $question_id, ':isc' => $is_correct]);

    if ($is_correct) {
        $feedback_exercicio = "<div class='alerta-sucesso'>✨ Resposta Correta! Excelente linha de raciocínio.</div>";
    } else {
        $feedback_exercicio = "<div class='alerta-erro'>❌ Alternativa Incorreta. Que tal rever o resumo do subtópico? A correta era a ($correta).</div>";
    }
    
    // Forçar recarga rápida dos dados para atualizar a barra lateral imediatamente após o clique
    header("Location: topico.php?id=" . $slug_atual);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $nome_topico_atual; ?> | Atomicamente</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght=400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    .layout-sala-aula { display: grid; grid-template-columns: 340px 1fr; min-height: calc(100vh - 65px); }
    
    /* SIDEBAR SINCRONIZADA */
    .sidebar-grade { background: white; border-right: 1px solid var(--borda); padding: 25px 20px; overflow-y: auto; max-height: calc(100vh - 65px); }
    .titulo-categoria { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cinza-texto); margin: 22px 0 10px 0; font-weight: 700; }
    
    .link-subtopico-wrapper { display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-radius: 8px; margin-bottom: 4px; text-decoration: none; color: #334155; transition: all 0.2s; }
    .link-subtopico-wrapper:hover { background: var(--roxo-suave); color: var(--roxo-base); }
    .link-subtopico-wrapper.ativo { background: var(--roxo-suave); color: var(--roxo-base); font-weight: 600; }
    .label-titulo { font-size: 0.88rem; max-width: 210px; }
    
    .badge-progresso { font-size: 0.75rem; font-weight: 700; padding: 2px 6px; border-radius: 6px; background: #f1f5f9; color: #64748b; }
    .link-subtopico-wrapper.ativo .badge-progresso, .link-subtopico-wrapper:hover .badge-progresso { background: white; color: var(--roxo-base); }

    /* ÁREA DE CONTEÚDO */
    .centro-aula { padding: 40px; background: #f8fafc; overflow-y: auto; max-height: calc(100vh - 65px); }
    .card-modulo { background: white; border-radius: 16px; border: 1px solid var(--borda); padding: 30px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
    
    /* SUBTÓPICOS / VIDEOAULAS */
    .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 12px; margin: 15px 0; background: #000; }
    .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
    
    /* EXERCÍCIOS */
    .opcao-exercicio { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s; }
    .opcao-exercicio:hover { border-color: var(--roxo-base); background: #f8fafc; }
    
    .alerta-sucesso { background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
    .alerta-erro { background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
  </style>
    <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem" style="background: var(--roxo-base);">SALA DE AULA</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 20px;">
        <?php if (verificarSeEhAdmin()): ?>
          <a href="admin.php" class="btn-acao" style="background: #7c3aed; color: white; padding: 8px 16px; font-size: 0.85rem; border-radius: 8px; text-decoration: none; font-weight: 700; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); border: none; cursor: pointer;">
            ⚙️ Painel Administradora
          </a>
        <?php endif; ?>

        <a href="materias.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.9rem;">← Voltar para os Tópicos</a>
        
        <a href="logout.php" style="color: #ef4444; text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-left: 5px; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1">Sair</a>
      </div>
    </div>
  </header>

  <div class="layout-sala-aula">
    
    <nav class="sidebar-grade">
      <h2 style="font-size: 1.1rem; color: var(--roxo-profundo); margin-top:0; font-weight:700;">Grade Temática</h2>
      
      <?php foreach ($frentes_sidebar as $frente): ?>
        <div class="titulo-categoria"><?php echo $frente['nome']; ?></div>
        <?php foreach ($frente['topicos'] as $topico): ?>
          <a href="topico.php?id=<?php echo $topico['slug']; ?>" class="link-subtopico-wrapper <?php echo ($topico['slug'] === $slug_atual) ? 'ativo' : ''; ?>">
             <span class="label-titulo"><?php echo $topico['nome']; ?></span>
             <span class="badge-progresso"><?php echo $topico['porcentagem']; ?>%</span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <main class="centro-aula">
      
      <div style="margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 2rem; color: var(--roxo-profundo); font-weight: 800;"><?php echo $nome_topico_atual; ?></h1>
        <p style="margin: 5px 0 0 0; color: var(--cinza-texto); font-size: 1rem;">Estuda a base teórica através dos subtópicos e consolida com exercícios oficiais do ENEM.</p>
      </div>

      <?php echo $feedback_exercicio; ?>

      <h2 style="color: var(--roxo-profundo); font-size: 1.3rem; font-weight: 700; margin-bottom: 15px;">📖 Subtópicos & Aulas Teóricas</h2>
      
      <?php if (empty($aulas_topico)): ?>
        <div class="card-modulo" style="color: var(--cinza-texto); font-size: 0.95rem;">
          Nenhum subtópico de leitura ou vídeo foi cadastrado para esta matéria ainda. Acede ao Painel Admin para alimentar esta secção.
        </div>
      <?php else: ?>
        <?php foreach ($aulas_topico as $aula): ?>
          <div class="card-modulo">
            <h3 style="margin-top: 0; color: var(--roxo-base); font-weight: 700;"><?php echo $aula['titulo']; ?></h3>
            
            <?php if (!empty($aula['video_url'])): ?>
              <div class="video-container">
                <iframe src="<?php echo $aula['video_url']; ?>" allowfullscreen></iframe>
              </div>
            <?php endif; ?>

            <?php if (!empty($aula['resumo'])): ?>
              <div style="line-height: 1.6; color: #334155; font-size: 0.98rem; margin-top: 15px;">
                <?php echo nl2br($aula['resumo']); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <h2 style="color: var(--roxo-profundo); font-size: 1.3rem; font-weight: 700; margin: 35px 0 15px 0;">📝 Banco de Exercícios Integrados</h2>

      <?php if (empty($questoes_topico)): ?>
        <div class="card-modulo" style="color: var(--cinza-texto); font-size: 0.95rem;">
          Grandioso! Não existem exercícios pendentes neste bloco ou o banco de dados precisa de ser alimentado no Painel Admin.
        </div>
      <?php else: ?>
        <?php $contador = 1; ?>
        <?php foreach ($questoes_topico as $questao): ?>
          <div class="card-modulo">
            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--roxo-base); letter-spacing: 0.05em;">Questão <?php echo $contador++; ?></span>
            <p style="font-size: 1.05rem; line-height: 1.6; color: var(--roxo-profundo); font-weight: 500; margin: 10px 0 20px 0;">
              <?php echo nl2br($questao['statement']); ?>
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
                <label class="opcao-exercicio">
                  <input type="radio" name="alternative_letter" value="<?php echo $alt['letter']; ?>" required style="accent-color: var(--roxo-base);">
                  <span style="font-weight: 700; color: var(--roxo-base);"><?php echo $alt['letter']; ?>)</span>
                  <span style="color: #334155; font-size: 0.95rem;"><?php echo $alt['text_content']; ?></span>
                </label>
              <?php endforeach; ?>

              <button type="submit" class="btn-acao" style="margin-top: 15px; display: inline-block; width: auto; padding: 12px 30px;">Enviar Resposta</button>
            </form>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </main>

  </div>

</body>
</html>
