<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 1. Puxar as Frentes e os seus respetivos Tópicos com o Cálculo de Progresso Individual
    $frentes_com_progresso = [];
    
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
        
        // Calcular a porcentagem em tempo de execução para cada um
        foreach ($topicos_lista as &$t) {
            if ($t['total_questoes'] > 0) {
                $t['porcentagem'] = round(($t['respondidas'] / $t['total_questoes']) * 100);
            } else {
                $t['porcentagem'] = 0; // 0% caso não existam exercícios cadastrados
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
      background: white; border-right: 1px solid var(--borda);
      padding: 25px 20px; overflow-y: auto; max-height: calc(100vh - 65px);
    }
    .titulo-categoria {
      font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;
      color: var(--cinza-texto); margin: 22px 0 10px 0; font-weight: 700;
    }
    
    /* ITEM DA BARRA LATERAL COM VISUAL DE PROGRESSO */
    .link-subtopico-wrapper {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 12px; border-radius: 8px; margin-bottom: 4px;
      text-decoration: none; color: #334155; transition: all 0.2s;
    }
    .link-subtopico-wrapper:hover { background: var(--roxo-suave); color: var(--roxo-base); }
    .label-titulo { font-size: 0.88rem; font-weight: 500; max-width: 220px; }
    .badge-progresso {
      font-size: 0.75rem; font-weight: 700; padding: 2px 6px; border-radius: 6px;
      background: #f1f5f9; color: #64748b; transition: all 0.2s;
    }
    .link-subtopico-wrapper:hover .badge-progresso { background: white; color: var(--roxo-base); }

    .conteudo-hub { padding: 40px; background: var(--cinza-fundo); overflow-y: auto; max-height: calc(100vh - 65px); }
    .hub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 30px; }
    
    /* CARDS DA GRADE CENTRALIZADA */
    .card-hub-topico {
      display: flex; justify-content: space-between; align-items: center;
      background: white; border: 1.5px solid #d8e2ef; border-radius: 14px; padding: 22px;
      text-decoration: none; transition: all 0.2s ease-in-out;
    }
    .card-hub-topico:hover { border-color: var(--roxo-vivo); box-shadow: 0 10px 20px rgba(109, 40, 217, 0.04); transform: translateY(-2px); }
    .card-hub-info { display: flex; flex-direction: column; gap: 6px; }
    .card-hub-tag { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; }
    .card-hub-titulo { font-size: 1rem; font-weight: 600; color: var(--roxo-profundo); }
    
    /* Barra visual interna do mini card */
    .mini-barra-progresso { width: 100px; height: 5px; background: #e2e8f0; border-radius: 10px; overflow: hidden; margin-top: 2px; }
    .mini-barra-preenchida { height: 100%; background: var(--sucesso); border-radius: 10px; }
  </style>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem">ENEM</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 20px;">
        <?php if (verificarSeEhAdmin()): ?>
          <a href="admin.php" class="btn-acao" style="background: #7c3aed; color: white; padding: 8px 16px; font-size: 0.85rem; border-radius: 8px; text-decoration: none; font-weight: 700; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); border: none; cursor: pointer;">
            ⚙️ Painel Administradora
          </a>
        <?php endif; ?>

        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Painel Inicial</a>
      </div>
    </div>
  </header>

  <div class="hub-layout-grid">
    
    <nav class="sidebar-grade">
      <h2 style="font-size: 1.1rem; color: var(--roxo-profundo); margin-top:0; font-weight:700;">Grade Temática</h2>
      
      <?php foreach ($frentes_com_progresso as $frente): ?>
        <div class="titulo-categoria"><?php echo $frente['nome']; ?></div>
        <?php foreach ($frente['topicos'] as $topico): ?>
          <a href="topico.php?id=<?php echo $topico['slug']; ?>" class="link-subtopico-wrapper">
             <span class="label-titulo"><?php echo $topico['nome']; ?></span>
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
            <a href="topico.php?id=<?php echo $topico['slug']; ?>" class="card-hub-topico">
              <div class="card-hub-info">
                <span class="card-hub-tag"><?php echo $frente['nome']; ?></span>
                <span class="card-hub-titulo"><?php echo $topico['nome']; ?></span>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 2px;">
                  <div class="mini-barra-progresso">
                    <div class="mini-barra-preenchida" style="width: <?php echo $topico['porcentagem']; ?>%;"></div>
                  </div>
                  <span style="font-size: 0.75rem; color: #64748b; font-weight: 600;"><?php echo $topico['porcentagem']; ?>%</span>
                </div>
              </div>
              <div style="font-size: 1.5rem; opacity: 0.7;"><?php echo $frente['icone']; ?></div>
            </a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </main>

  </div>

</body>
</html>
