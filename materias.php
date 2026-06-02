<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 1. Puxar a preferência de FOCO do aluno
    $stmtUser = $pdo->prepare("SELECT frente_foco FROM users WHERE id = :uid");
    $stmtUser->execute([':uid' => $user_id]);
    $foco_aluno = $stmtUser->fetchColumn() ?: ''; // Ex: 'geral', 'fisico', 'organica', 'ambiental'

    // 2. Puxar as Frentes e os seus respetivos Tópicos com o Cálculo de Progresso
    $frentes_com_progresso = [];
    
    $stmtFrentes = $pdo->query("SELECT * FROM frentes ORDER BY id ASC");
    while ($frente = $stmtFrentes->fetch()) {
        
        // Verifica se esta frente é o foco do aluno (usando stripos para ignorar maiúsculas/minúsculas)
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
        $frente['eh_foco'] = $eh_foco; // Guardamos essa info para usar no HTML
        
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
        
        // Calcular a percentagem em tempo de execução
        foreach ($topicos_lista as &$t) {
            if ($t['total_questoes'] > 0) {
                $t['porcentagem'] = round(($t['respondidas'] / $t['total_questoes']) * 100);
            } else {
                $t['porcentagem'] = 0; 
            }
        }
        
        $frente['topicos'] = $topicos_lista;
        $frentes_com_progresso[] = $frente;
    }

} catch (PDOException $e) {
    die("Erro na conexão dinâmica: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conteúdos ENEM | Atomicamente</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    .hub-layout-grid { display: grid; grid-template-columns: 340px 1fr; min-height: calc(100vh - 65px); }
    
    .sidebar-grade {
      background: var(--bg-global, white); border-right: 1px solid var(--borda);
      padding: 25px 20px; overflow-y: auto; max-height: calc(100vh - 65px);
    }
    .titulo-categoria {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;
      color: var(--cinza-texto); margin: 22px 0 10px 0; font-weight: 700;
    }
    
    /* BADGE DE FOCO PARA A SIDEBAR */
    .badge-foco-sidebar {
      background: var(--roxo-base); color: white; font-size: 0.65rem;
      padding: 2px 6px; border-radius: 4px; letter-spacing: 0;
    }
    
    .link-subtopico-wrapper {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 12px; border-radius: 8px; margin-bottom: 4px;
      text-decoration: none; color: var(--texto-principal, #334155); transition: all 0.2s;
    }
    .link-subtopico-wrapper:hover { background: var(--roxo-suave, #f3e8ff); color: var(--roxo-base); }
    .label-titulo { font-size: 0.88rem; font-weight: 500; max-width: 220px; }
    .badge-progresso {
      font-size: 0.75rem; font-weight: 700; padding: 2px 6px; border-radius: 6px;
      background: var(--borda, #f1f5f9); color: var(--cinza-texto, #64748b); transition: all 0.2s;
    }
    .link-subtopico-wrapper:hover .badge-progresso { background: white; color: var(--roxo-base); }

    .conteudo-hub { padding: 40px; background: var(--bg-card, #f8fafc); overflow-y: auto; max-height: calc(100vh - 65px); }
    .hub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 30px; }
    
    .card-hub-topico {
      position: relative; /* Necessário para a tag flutuante */
      display: flex; justify-content: space-between; align-items: center;
      background: var(--bg-global, white); border: 1.5px solid var(--borda, #d8e2ef); 
      border-radius: 14px; padding: 22px;
      text-decoration: none; transition: all 0.2s ease-in-out; overflow: hidden;
    }
    .card-hub-topico:hover { border-color: var(--roxo-vivo); box-shadow: 0 10px 20px rgba(109, 40, 217, 0.04); transform: translateY(-2px); }
    
    /* ESTILOS EXCLUSIVOS PARA MATÉRIAS DE FOCO NO CARD */
    .card-foco {
      border: 2px solid var(--roxo-base);
      background: linear-gradient(to bottom right, var(--bg-global, white), rgba(139, 92, 246, 0.05));
    }
    .tag-foco-card {
      position: absolute; top: 12px; right: 12px;
      background: var(--roxo-base); color: white;
      font-size: 0.65rem; font-weight: 800; padding: 3px 8px;
      border-radius: 12px; text-transform: uppercase; letter-spacing: 0.05em;
    }

    .card-hub-info { display: flex; flex-direction: column; gap: 6px; }
    .card-hub-tag { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; }
    .card-hub-titulo { font-size: 1rem; font-weight: 600; color: var(--roxo-profundo, #4c1d95); }
    
    .mini-barra-progresso { width: 100px; height: 5px; background: var(--borda, #e2e8f0); border-radius: 10px; overflow: hidden; margin-top: 2px; }
    .mini-barra-preenchida { height: 100%; background: var(--sucesso); border-radius: 10px; }
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
        <?php if (verificarSeEhAdmin() && $pagina_atual !== 'admin.php'): ?>
          <a href="admin.php" class="btn-acao" style="background: #7c3aed; color: white; padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; text-decoration: none; font-weight: 700;">⚙️ Gerenciar</a>
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
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 14px; font-size: 0.88rem; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            👤 <?php echo explode(' ', $_SESSION['user_nome'] ?? 'Estudante')[0]; ?> <span style="font-size: 0.65rem;">▼</span>
          </button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <a href="perfil.php" class="dropdown-item">🧑‍🎓 Preferências do Perfil</a>
            <div class="dropdown-divisor"></div>
            <a href="logout.php" class="dropdown-item sair">🚪 Sair</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="hub-layout-grid">
    
    <nav class="sidebar-grade">
      <h2 style="font-size: 1.1rem; color: var(--roxo-profundo); margin-top:0; font-weight:700;">Grade Temática</h2>
      
      <?php foreach ($frentes_com_progresso as $frente): ?>
        <div class="titulo-categoria">
          <?php echo htmlspecialchars($frente['nome']); ?>
          <?php if ($frente['eh_foco']): ?>
            <span class="badge-foco-sidebar">⭐ FOCO</span>
          <?php endif; ?>
        </div>

        <?php foreach ($frente['topicos'] as $topico): ?>
          <a href="topico.php?id=<?php echo htmlspecialchars($topico['slug'] ?? $topico['id']); ?>" class="link-subtopico-wrapper">
             <span class="label-titulo"><?php echo htmlspecialchars($topico['nome']); ?></span>
             <span class="badge-progresso"><?php echo $topico['porcentagem']; ?>%</span>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <main class="conteudo-hub">
      <div style="border-bottom: 1px solid var(--borda); padding-bottom: 20px;">
        <h1 style="margin: 0; font-size: 1.8rem; color: var(--roxo-profundo); font-weight: 800;">O que vamos exercitar hoje?</h1>
        <p style="margin: 5px 0 0 0; color: var(--cinza-texto); font-size: 0.95rem;">Selecione uma das frentes abaixo ou gerencie o progresso das suas metas de estudo.</p>
      </div>

      <div class="hub-grid">
        <?php foreach ($frentes_com_progresso as $frente): ?>
          <?php foreach ($frente['topicos'] as $topico): ?>
            <a href="topico.php?id=<?php echo htmlspecialchars($topico['slug'] ?? $topico['id']); ?>" class="card-hub-topico <?php echo $frente['eh_foco'] ? 'card-foco' : ''; ?>">
              
              <?php if ($frente['eh_foco']): ?>
                <div class="tag-foco-card">⭐ Recomendado</div>
              <?php endif; ?>

              <div class="card-hub-info">
                <span class="card-hub-tag"><?php echo htmlspecialchars($frente['nome']); ?></span>
                <span class="card-hub-titulo"><?php echo htmlspecialchars($topico['nome']); ?></span>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                  <div class="mini-barra-progresso">
                    <div class="mini-barra-preenchida" style="width: <?php echo $topico['porcentagem']; ?>%;"></div>
                  </div>
                  <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?php echo $topico['porcentagem']; ?>%</span>
                </div>
              </div>
              <div style="font-size: 1.5rem; opacity: 0.7;"><?php echo $frente['icone'] ?? '📚'; ?></div>
            </a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </main>

  </div>

</body>
</html>
