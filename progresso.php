<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // MÉTRICAS GERAIS
    $stmtGeral = $pdo->prepare("SELECT COUNT(*) as total_resolvidas, SUM(is_correct) as total_acertos FROM user_progress WHERE user_id = :uid");
    $stmtGeral->execute([':uid' => $user_id]);
    $dadosGerais = $stmtGeral->fetch(PDO::FETCH_ASSOC);

    $total_resolvidas = $dadosGerais['total_resolvidas'] ?? 0;
    $total_acertos = $dadosGerais['total_acertos'] ?? 0;
    $total_erros = $total_resolvidas - $total_acertos;
    $taxa_acerto_geral = $total_resolvidas > 0 ? round(($total_acertos / $total_resolvidas) * 100) : 0;

    // DESEMPENHO POR FRENTE
    $stmtFrentes = $pdo->prepare("
        SELECT f.nome as frente_nome, COUNT(up.question_id) as total_respondidas, SUM(up.is_correct) as total_acertos
        FROM frentes f JOIN topicos t ON t.frente_id = f.id JOIN questions q ON q.subtopic_id = t.id
        JOIN user_progress up ON up.question_id = q.id WHERE up.user_id = :uid GROUP BY f.id ORDER BY total_respondidas DESC
    ");
    $stmtFrentes->execute([':uid' => $user_id]);
    $desempenho_frentes = $stmtFrentes->fetchAll(PDO::FETCH_ASSOC);

    // MEDALHAS DO ALUNO
    $stmtMedalhas = $pdo->prepare("
        SELECT m.*, um.conquistada_em FROM medalhas m
        LEFT JOIN user_medalhas um ON m.id = um.medalha_id AND um.user_id = :uid ORDER BY m.id ASC
    ");
    $stmtMedalhas->execute([':uid' => $user_id]);
    $lista_medalhas = $stmtMedalhas->fetchAll(PDO::FETCH_ASSOC);

    // OFENSIVA DO ALUNO (STREAK)
    $stmtStreak = $pdo->prepare("SELECT streak FROM users WHERE id = :uid");
    $stmtStreak->execute([':uid' => $user_id]);
    $streak_aluno = $stmtStreak->fetchColumn() ?: 0;

} catch (PDOException $e) {
    die("Erro ao carregar o dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Progresso | Atomicamente</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); }
    .container-dashboard { max-width: 1050px; margin: 60px auto; padding: 0 20px; }
    
    .cabecalho-pagina { text-align: center; margin-bottom: 60px; }
    .titulo-dash { font-size: 2.8rem; font-weight: 800; letter-spacing: -0.04em; margin: 0; color: var(--texto-principal); }
    .subtitulo-dash { font-size: 1.15rem; color: var(--texto-secundario); margin-top: 12px; }
    
    /* CARDS DE ESTATÍSTICAS */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 60px; }
    .stat-card { background: var(--bg-card); border-radius: 24px; border: 1px solid var(--borda); padding: 40px 30px; text-align: center; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.03); transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .stat-card:hover { transform: translateY(-6px); box-shadow: 0 12px 30px -5px rgba(0,0,0,0.08); }
    .stat-label { font-size: 0.85rem; text-transform: uppercase; font-weight: 800; color: var(--texto-secundario); letter-spacing: 0.05em; margin-bottom: 12px; }
    .stat-value { font-size: 4rem; font-weight: 800; color: var(--roxo-base); line-height: 1; letter-spacing: -0.04em; }
    .stat-desc { font-size: 0.95rem; color: var(--texto-secundario); margin-top: 15px; font-weight: 500; }
    
    .card-destaque { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; box-shadow: 0 10px 30px -5px rgba(79, 70, 229, 0.4); }
    .card-destaque .stat-label, .card-destaque .stat-desc { color: rgba(255,255,255,0.85); }
    .card-destaque .stat-value { color: white; }

    /* SEÇÕES DETALHADAS */
    .secao-detalhada { background: var(--bg-card); border-radius: 24px; border: 1px solid var(--borda); padding: 50px; margin-bottom: 50px; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.02); }
    .titulo-secao { font-size: 1.6rem; font-weight: 800; margin-top: 0; margin-bottom: 35px; letter-spacing: -0.03em; display: flex; align-items: center; gap: 10px; }

    /* DESEMPENHO POR FRENTE */
    .frente-row { margin-bottom: 30px; }
    .frente-row:last-child { margin-bottom: 0; }
    .frente-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 12px; }
    .frente-nome { font-weight: 800; font-size: 1.1rem; color: var(--texto-principal); }
    .frente-numeros { font-size: 0.95rem; color: var(--texto-secundario); font-weight: 600; }
    .barra-bg { background: var(--bg-global); height: 16px; border-radius: 20px; overflow: hidden; border: 1px solid var(--borda); }
    .barra-fill { height: 100%; border-radius: 20px; transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1); }
    
    .fill-bom { background: linear-gradient(90deg, #10b981, #34d399); }
    .fill-medio { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .fill-ruim { background: linear-gradient(90deg, #ef4444, #f87171); }

    /* NOVO DESIGN DA GALERIA DE MEDALHAS 🏆 */
    .grid-medalhas { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
    
    .card-medalha { 
      background: var(--bg-card); border-radius: 20px; padding: 35px 25px; 
      text-align: center; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
      position: relative; border: 2px solid transparent;
    }
    
    .medalha-conquistada { 
      border-color: var(--roxo-base); 
      background: linear-gradient(145deg, var(--bg-card), rgba(139, 92, 246, 0.03));
      box-shadow: 0 10px 30px -5px rgba(139, 92, 246, 0.15); 
    }
    .medalha-conquistada:hover { transform: translateY(-8px); box-shadow: 0 15px 35px -5px rgba(139, 92, 246, 0.25); }
    
    .medalha-bloqueada { 
      background: var(--bg-global); border: 2px dashed var(--borda); 
      opacity: 0.6; filter: grayscale(100%); 
    }
    
    .icone-medalha { font-size: 4.5rem; margin-bottom: 20px; line-height: 1; display: inline-block; transition: transform 0.4s ease; }
    .medalha-conquistada:hover .icone-medalha { transform: scale(1.15) rotate(8deg); }
    
    .titulo-medalha { margin: 0 0 10px 0; color: var(--texto-principal); font-size: 1.2rem; font-weight: 800; letter-spacing: -0.02em; }
    .desc-medalha { margin: 0; color: var(--texto-secundario); font-size: 0.9rem; line-height: 1.5; font-weight: 500; }
    
    .selo-data { margin-top: 20px; font-size: 0.75rem; font-weight: 800; color: var(--roxo-base); background: var(--roxo-suave); padding: 6px 14px; border-radius: 20px; display: inline-block; letter-spacing: 0.05em; text-transform: uppercase; }
    .selo-bloqueada { margin-top: 20px; font-size: 0.75rem; font-weight: 800; color: var(--texto-secundario); background: var(--bg-card); border: 1px solid var(--borda); padding: 6px 14px; border-radius: 20px; display: inline-block; letter-spacing: 0.05em; text-transform: uppercase; }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash" style="border-bottom: 1px solid var(--borda); background: var(--bg-card);">
    <div class="container nav-dash" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      <a href="dashboard.php" class="marca-dash" style="font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; letter-spacing: -0.03em;">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 34px; border-radius: 8px;" />
        Atomicamente
      </a>
      
      <div style="display: flex; align-items: center; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 8px; background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.2); padding: 8px 14px; border-radius: 12px; font-weight: 800; color: #ea580c; font-size: 0.95rem;">
          🔥 <?php echo $streak_aluno; ?> Dias
        </div>
        
        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 700; font-size: 0.95rem; transition: opacity 0.2s;">Painel Inicial</a>
        <a href="perfil.php" style="background: var(--roxo-base); color: white; padding: 10px 20px; font-size: 0.95rem; border-radius: 12px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3);">👤 Voltar ao Perfil</a>
      </div>
    </div>
  </header>

  <div class="container-dashboard">
    <div class="cabecalho-pagina">
      <h1 class="titulo-dash">Seu Boletim & Conquistas</h1>
      <p class="subtitulo-dash">Analise os seus dados de estudo, descubra os seus pontos fortes e acompanhe o seu legado.</p>
    </div>

    <!-- CARDS DE ESTATÍSTICAS GERAIS -->
    <div class="stats-grid">
      <div class="stat-card card-destaque">
        <div class="stat-label">Taxa de Acerto Global</div>
        <div class="stat-value"><?php echo $taxa_acerto_geral; ?>%</div>
        <div class="stat-desc">Precisão em todo o histórico</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Bateria Resolvida</div>
        <div class="stat-value" style="color: var(--texto-principal);"><?php echo $total_resolvidas; ?></div>
        <div class="stat-desc">Questões finalizadas no total</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Balanço de Respostas</div>
        <div class="stat-value" style="font-size: 2.5rem; margin-top: 15px; display: flex; justify-content: center; align-items: center; gap: 15px;">
          <span style="color: #10b981;"><?php echo $total_acertos; ?> <span style="font-size: 1rem; color: var(--texto-secundario); font-weight: 700;">Hits</span></span> 
          <span style="color: var(--borda); font-weight: 400; font-size: 1.5rem;">|</span> 
          <span style="color: #ef4444;"><?php echo $total_erros; ?> <span style="font-size: 1rem; color: var(--texto-secundario); font-weight: 700;">Miss</span></span>
        </div>
        <div class="stat-desc">Acertos comparados aos erros</div>
      </div>
    </div>

    <!-- GALERIA DE MEDALHAS LUXUOSA 🏆 -->
    <div class="secao-detalhada">
      <h2 class="titulo-secao">🏆 Galeria de Honra</h2>
      <div class="grid-medalhas">
        <?php foreach ($lista_medalhas as $medalha): ?>
          <?php $conquistada = !empty($medalha['conquistada_em']); ?>
          <div class="card-medalha <?php echo $conquistada ? 'medalha-conquistada' : 'medalha-bloqueada'; ?>">
            <div class="icone-medalha"><?php echo htmlspecialchars($medalha['icone']); ?></div>
            <h4 class="titulo-medalha"><?php echo htmlspecialchars($medalha['nome']); ?></h4>
            <p class="desc-medalha"><?php echo htmlspecialchars($medalha['descricao']); ?></p>
            
            <?php if ($conquistada): ?>
              <div class="selo-data">Desbloqueada em <?php echo date('d/m/Y', strtotime($medalha['conquistada_em'])); ?></div>
            <?php else: ?>
              <div class="selo-bloqueada">🔒 Desafio Pendente</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- DESEMPENHO POR FRENTE REFINADO -->
    <div class="secao-detalhada">
      <h2 class="titulo-secao">📊 Raio-X por Frente de Estudo</h2>
      <?php if (empty($desempenho_frentes)): ?>
        <p style="text-align: center; color: var(--texto-secundario); font-size: 1.1rem; padding: 20px;">O seu Raio-X será gerado assim que finalizar a primeira bateria de exercícios.</p>
      <?php else: ?>
        <?php foreach ($desempenho_frentes as $frente): ?>
          <?php 
            $taxa_frente = round(($frente['total_acertos'] / $frente['total_respondidas']) * 100); 
            $classe_cor = $taxa_frente < 40 ? 'fill-ruim' : ($taxa_frente < 70 ? 'fill-medio' : 'fill-bom');
          ?>
          <div class="frente-row">
            <div class="frente-header">
              <span class="frente-nome"><?php echo htmlspecialchars($frente['frente_nome']); ?></span>
              <span class="frente-numeros"><b><?php echo $taxa_frente; ?>%</b> (<?php echo $frente['total_acertos']; ?> de <?php echo $frente['total_respondidas']; ?>)</span>
            </div>
            <div class="barra-bg"><div class="barra-fill <?php echo $classe_cor; ?>" style="width: <?php echo $taxa_frente; ?>%;"></div></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>
</body>
</html>
