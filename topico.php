<?php
session_start();
require_once 'config.php';

// 1. Captura o slug vindo da URL (ex: topico.php?id=modelos-atomicos)
$slug_topico = $_GET['id'] ?? 'modelos-atomicos';

try {
    // 2. Procura o subtópico atual pelo slug
    $stmt = $pdo->prepare("SELECT * FROM subtopics WHERE slug = :slug");
    $stmt->execute([':slug' => $slug_topico]);
    $aula = $stmt->fetch();

    // 3. Carrega todos os subtópicos para o menu lateral (Focado em Química Geral - subject_id = 1)
    $stmtMenu = $pdo->prepare("SELECT titulo, slug FROM subtopics WHERE subject_id = 1");
    $stmtMenu->execute();
    $menuSubtopicos = $stmtMenu->fetchAll();

} catch (PDOException $e) {
    die("Erro na ligação à base de dados: " . $e->getMessage());
}

// Fallback de segurança caso a tabela esteja vazia no teu localhost
if (!$aula) {
    $aula = [
        'id' => 0,
        'titulo' => 'Conteúdo Demonstrativo (Insira dados no MySQL)',
        'texto_aula' => '<p>Para ver dados reais aqui, publique uma aula pelo painel de Admin ou insira um registo na tabela <code>subtopics</code>.</p>',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'fontes' => 'Referências em catalogação no banco de dados.'
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($aula['titulo']); ?> | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="css/plataforma.css">
</head>
<body class="dash-body">
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="index.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 30px; border-radius: 6px;" />
        Atomicamente
      </a>
      <div class="user-info">
        <a href="dashboard.php" style="color: var(--roxo-base); font-weight: bold; text-decoration: none;">← Voltar ao Meu Painel</a>
      </div>
    </div>
  </header>

  <div class="container workspace-layout" style="display: grid; grid-template-columns: 280px 1fr; gap: 25px; padding: 30px 0;">
    
    <aside style="background: #fff; border: 1px solid var(--borda); border-radius: 16px; padding: 20px; height: fit-content;">
      <h3 style="font-size: 1rem; color: var(--roxo-base); margin-bottom: 15px;">🧪 Química Geral</h3>
      <div style="display: flex; flex-direction: column; gap: 10px;">
        
        <?php if (count($menuSubtopicos) > 0): ?>
            <?php foreach ($menuSubtopicos as $item): ?>
                <a href="topico.php?id=<?php echo $item['slug']; ?>" style="display: flex; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 10px; border-radius: 8px; <?php echo ($slug_topico === $item['slug']) ? 'background: #f3e8ff; border-left: 4px solid var(--roxo-vivo); font-weight: bold;' : ''; ?>">
                  <div>○</div>
                  <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($item['titulo']); ?></span>
                </a>
            <?php endpph; ?>
        <?php else: ?>
            <a href="topico.php?id=modelos-atomicos" style="display: flex; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 10px; border-radius: 8px; background: #f3e8ff; border-left: 4px solid var(--roxo-vivo); font-weight: bold;">
              <span style="font-size: 0.9rem;">Modelos Atómicos</span>
            </a>
            <a href="topico.php?id=estequiometria" style="display: flex; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 10px; border-radius: 8px;">
              <span style="font-size: 0.9rem;">Estequiometria</span>
            </a>
        <?php endif; ?>
        
      </div>
    </aside>

    <main>
      <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--borda); margin-bottom: 25px;">
        <button id="tabTexto" class="btn-aba-sala active" style="background: none; border: none; padding: 12px 20px; font-weight: bold; color: var(--roxo-base); cursor: pointer; border-bottom: 3px solid var(--roxo-vivo);">📝 Texto Base</button>
        <button id="tabVideo" class="btn-aba-sala" style="background: none; border: none; padding: 12px 20px; font-weight: bold; color: var(--cinza-texto); cursor: pointer; border-bottom: 3px solid transparent;">🎥 Videoaula & Fontes</button>
        <button id="tabExercicios" class="btn-aba-sala" style="background: none; border: none; padding: 12px 20px; font-weight: bold; color: var(--cinza-texto); cursor: pointer; border-bottom: 3px solid transparent;">📝 Exercícios</button>
      </div>

      <div id="painelTexto">
        <div style="background: white; padding: 30px; border-radius: 16px; border: 1px solid var(--borda);">
          <h2><?php echo htmlspecialchars($aula['titulo']); ?></h2>
          <div style="margin-top: 15px; line-height: 1.7; color: #211032;">
            <?php echo $aula['texto_aula']; ?>
          </div>
        </div>
      </div>

      <div id="painelVideo" style="display: none;">
        <div style="background: white; padding: 30px; border-radius: 16px; border: 1px solid var(--borda);">
          <h3>Videoaula de Apoio</h3>
          <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 20px 0; border-radius: 12px; background: #000;">
            <iframe src="<?php echo htmlspecialchars($aula['video_url']); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>
          </div>
          <h4>Fontes Científicas:</h4>
          <p style="color: var(--cinza-texto); margin-top: 8px; white-space: pre-line;">
            <?php echo htmlspecialchars($aula['fontes']); ?>
          </p>
        </div>
      </div>

      <div id="painelExercicios" style="display: none;">
        <div style="background: #eff6ff; color: #1e40af; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; margin-bottom: 20px;">
          💾 Respostas síncronas. Cada clique atualiza o teu gráfico de proficiência no Painel Geral.
        </div>
        
        <?php
        // Procura as questões ligadas a este subtopico_id
        $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subtopic_id = :sid");
        $stmtQ->execute([':sid' => $aula['id']]);
        $questoes = $stmtQ->fetchAll();

        if (count($questoes) > 0):
            foreach ($questoes as $q): ?>
                <div class='questao-container' style='background: white; padding: 25px; border-radius: 16px; border: 1px solid var(--borda); margin-bottom: 20px;'>
                  <p><strong>Questão (ENEM)</strong>: <?php echo htmlspecialchars($q['enunciado']); ?></p>
                  
                  <?php
                  $stmtA = $pdo->prepare("SELECT * FROM alternatives WHERE question_id = :qid ORDER BY letra");
                  $stmtA->execute([':qid' => $q['id']]);
                  $alternativas = $stmtA->fetchAll();
                  
                  foreach ($alternativas as $alt):
                      $classeCorreta = $alt['eh_correta'] ? 'correct' : '';
                  ?>
                      <div class='opcao-radio-card <?php echo $classeCorreta; ?>' data-questao='<?php echo $q['id']; ?>' data-id='<?php echo $alt['id']; ?>' style='padding: 15px; border: 1px solid var(--borda); border-radius: 10px; margin-top: 12px; cursor: pointer;'>
                        <label style='cursor:pointer;'><input type='radio' name='q_<?php echo $q['id']; ?>'> <?php echo htmlspecialchars($alt['letra']); ?>) <?php echo htmlspecialchars($alt['texto_alternativa']); ?></label>
                      </div>
                  <?php endforeach; ?>
                </div>
            <?php endforeach; 
        else: ?>
            <div class='questao-container' style='background: white; padding: 25px; border-radius: 16px; border: 1px solid var(--borda);'>
              <p><strong>Questão 1 (ENEM)</strong>: O modelo atómico que introduziu os eletrões foi proposto por:</p>
              <div class="opcao-radio-card" data-questao="1" data-id="1" style="padding: 15px; border: 1px solid var(--borda); border-radius: 10px; margin-top: 12px; cursor: pointer;">
                <label><input type="radio" name="demo"> A) John Dalton</label>
              </div>
              <div class="opcao-radio-card correct" data-questao="1" data-id="2" style="padding: 15px; border: 1px solid var(--borda); border-radius: 10px; margin-top: 12px; cursor: pointer;">
                <label><input type="radio" name="demo"> B) J.J. Thomson (Correta)</label>
              </div>
            </div>
        <?php endif; ?>
      </div>
    </main>
  </div>

  <script src="js/plataforma.js"></script>
</body>
</html>
