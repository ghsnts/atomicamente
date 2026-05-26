<?php
session_start();
require_once 'config.php';

// Captura o ID vindo da URL (ex: topico.php?id=estequiometria). Se não houver, assume modelos-atomicos
$id_topico = $_GET['id'] ?? 'modelos-atomicos';

// Faz a busca em tempo real na base de dados
$stmt = $pdo->prepare("SELECT * FROM subtopicos WHERE id = :id");
$stmt->execute([':id' => $id_topico]);
$aula = $stmt->fetch();

// Fallback de segurança: Caso a BD esteja vazia, exibe um texto amigável em vez de quebrar a página
if (!$aula) {
    $aula = [
        'nome' => 'Conteúdo em Desenvolvimento',
        'texto_aula' => '<p>O corpo docente ainda está a preparar os materiais textuais e científicos para este tópico do ENEM. Volte brevemente!</p>',
        'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'fontes' => 'Referências em catalogação.'
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($aula['nome']); ?> | Atomicamente</title>
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
        <a href="materias.php" style="color: var(--roxo-base); font-weight: bold; text-decoration: none;">← Módulos</a>
      </div>
    </div>
  </header>

  <div class="container workspace-layout" style="display: grid; grid-template-columns: 280px 1fr; gap: 25px; padding: 30px 0;">
    
    <aside style="background: #fff; border: 1px solid var(--borda); border-radius: 16px; padding: 20px; height: fit-content;">
      <h3 style="font-size: 1rem; color: var(--roxo-base); margin-bottom: 15px;">Química Geral</h3>
      <div style="display: flex; flex-direction: column; gap: 10px;">
        
        <a href="topico.php?id=modelos-atomicos" style="display: flex; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 10px; border-radius: 8px; <?php echo ($id_topico === 'modelos-atomicos') ? 'background: #f3e8ff; border-left: 4px solid var(--roxo-vivo); font-weight: bold;' : ''; ?>">
          <div style="color: #16a34a; font-weight: bold;">✓</div>
          <div>
            <span style="display: block; font-size: 0.9rem;">Modelos Atómicos</span>
          </div>
        </a>
        
        <a href="topico.php?id=estequiometria" style="display: flex; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 10px; border-radius: 8px; <?php echo ($id_topico === 'estequiometria') ? 'background: #f3e8ff; border-left: 4px solid var(--roxo-vivo); font-weight: bold;' : ''; ?>">
          <div style="color: #ccc;">○</div>
          <div>
            <span style="display: block; font-size: 0.9rem;">Estequiometria</span>
          </div>
        </a>
        
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
          <h2><?php echo htmlspecialchars($aula['nome']); ?></h2>
          <div style="margin-top: 15px; line-height: 1.7; color: #211032;">
            <?php echo $aula['texto_aula']; // Renderiza o texto e respeita tags HTML digitadas pelo professor ?>
          </div>
        </div>
      </div>

      <div id="painelVideo" style="display: none;">
        <div style="background: white; padding: 30px; border-radius: 16px; border: 1px solid var(--borda);">
          <h3>Videoaula de Apoio</h3>
          <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 20px 0; border-radius: 12px; background: #000;">
            <iframe src="<?php echo htmlspecialchars($aula['video_url']); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>
          </div>
          <h4>Fontes Científicas e Referências:</h4>
          <p style="color: var(--cinza-texto); margin-top: 8px; white-space: pre-line; line-height: 1.6;">
            <?php echo htmlspecialchars($aula['fontes']); ?>
          </p>
        </div>
      </div>

      <div id="painelExercicios" style="display: none;">
        <div style="background: #eff6ff; color: #1e40af; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; margin-bottom: 20px;">💾 Respostas em tempo real sincronizadas com o teu painel de proficiência.</div>
        
        <div style="background: white; padding: 25px; border-radius: 16px; border: 1px solid var(--borda);">
          <p><strong>Questão 1 (ENEM)</strong>: O modelo atómico que introduziu a natureza elétrica da matéria e a existência de eletrões foi proposto por:</p>
          
          <div class="opcao-radio-card" style="padding: 15px; border: 1px solid var(--borda); border-radius: 10px; margin-top: 12px; cursor: pointer;">
            <label><input type="radio" name="q1" value="A"> A) John Dalton</label>
          </div>
          <div class="opcao-radio-card correct" style="padding: 15px; border: 1px solid var(--borda); border-radius: 10px; margin-top: 12px; cursor: pointer;">
            <label><input type="radio" name="q1" value="B"> B) J.J. Thomson (Alternativa Correta)</label>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script src="js/plataforma.js"></script>
</body>
</html>
