<?php
session_start();
require_once 'config.php';

// 1. Proteção: Garante que o aluno está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // =========================================================================
    // MÉTRICAS GERAIS DO ALUNO
    // =========================================================================
    $stmtGeral = $pdo->prepare("
        SELECT 
            COUNT(*) as total_resolvidas,
            SUM(is_correct) as total_acertos
        FROM user_progress 
        WHERE user_id = :uid
    ");
    $stmtGeral->execute([':uid' => $user_id]);
    $dadosGerais = $stmtGeral->fetch(PDO::FETCH_ASSOC);

    $total_resolvidas = $dadosGerais['total_resolvidas'] ?? 0;
    $total_acertos = $dadosGerais['total_acertos'] ?? 0;
    $total_erros = $total_resolvidas - $total_acertos;
    $taxa_acerto_geral = $total_resolvidas > 0 ? round(($total_acertos / $total_resolvidas) * 100) : 0;

    // =========================================================================
    // DESEMPENHO DETALHADO POR FRENTE (CATEGORIA)
    // =========================================================================
    // Esta query cruza as respostas do aluno com a frente à qual a questão pertence
    $stmtFrentes = $pdo->prepare("
        SELECT 
            f.nome as frente_nome,
            COUNT(up.question_id) as total_respondidas,
            SUM(up.is_correct) as total_acertos
        FROM frentes f
        JOIN topicos t ON t.frente_id = f.id
        JOIN questions q ON q.subtopic_id = t.id
        JOIN user_progress up ON up.question_id = q.id
        WHERE up.user_id = :uid
        GROUP BY f.id
        ORDER BY total_respondidas DESC
    ");
    $stmtFrentes->execute([':uid' => $user_id]);
    $desempenho_frentes = $stmtFrentes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao carregar o dashboard analítico: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Progresso | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); }
    
    .container-dashboard { max-width: 1000px; margin: 50px auto; padding: 0 20px; }
    
    .cabecalho-pagina { text-align: center; margin-bottom: 50px; }
    .titulo-dash { font-size: 2.5rem; font-weight: 800; letter-spacing: -0.03em; color: var(--texto-principal); margin: 0; }
    .subtitulo-dash { font-size: 1.1rem; color: var(--texto-secundario); margin-top: 10px; }

    /* GRID DE CARDS GERAIS */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 50px; }
    
    .stat-card { 
      background: var(--bg-card); border-radius: 20px; border: 1px solid var(--borda); 
      padding: 35px 25px; text-align: center; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.02);
      transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    
    .stat-label { font-size: 0.85rem; text-transform: uppercase; font-weight: 800; color: var(--texto-secundario); letter-spacing: 0.05em; margin-bottom: 10px; }
    .stat-value { font-size: 3.5rem; font-weight: 800; color: var(--roxo-base); line-height: 1; letter-spacing: -0.03em; }
    .stat-desc { font-size: 0.9rem; color: var(--texto-secundario); margin-top: 10px; font-weight: 500; }

    /* DESTAQUE DE TAXA DE ACERTO */
    .card-destaque { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; }
    .card-destaque .stat-label, .card-destaque .stat-desc { color: rgba(255,255,255,0.8); }
    .card-destaque .stat-value { color: white; }

    /* ÁREA DETALHADA POR FRENTE */
    .secao-detalhada { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--borda); padding: 40px; box-shadow: 0 4px 15px -3px rgba(0,0,0,0.02); }
    .titulo-secao { font-size: 1.4rem; font-weight: 800; margin-top: 0; margin-bottom: 30px; letter-spacing: -0.02em; }

    .frente-row { margin-bottom: 25px; }
    .frente-row:last-child { margin-bottom: 0; }
    
    .frente-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; }
    .frente-nome { font-weight: 700; font-size: 1.05rem; }
    .frente-numeros { font-size: 0.9rem; color: var(--texto-secundario); font-weight: 600; }
    .frente-numeros b { color: var(--roxo-base); font-size: 1.05rem; }

    .barra-bg { background: var(--bg-global); height: 14px; border-radius: 10px; border: 1px solid var(--borda); overflow: hidden; }
    .barra-fill { height: 100%; border-radius: 10px; transition: width 1s cubic-bezier(0.4, 0, 0.2, 1); }
    
    /* CORES DINÂMICAS BASEADAS NO DESEMPENHO */
    .fill-bom { background: linear-gradient(90deg, #10b981, #059669); }    /* Verde (>= 70%) */
    .fill-medio { background: linear-gradient(90deg, #f59e0b, #d97706); }  /* Laranja (40-69%) */
    .fill-ruim { background: linear-gradient(90deg, #ef4444, #dc2626); }    /* Vermelho (< 40%) */

    .empty-state { text-align: center; padding: 40px; color: var(--texto-secundario); font-size: 1.1rem; font-style: italic; }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash" style="border-bottom: 1px solid var(--borda); background: var(--bg-card);">
    <div class="container nav-dash" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      
      <a href="dashboard.php" class="marca-dash" style="font-family: 'Inter', sans-serif; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.03em; display: flex; align-items: center; gap: 10px;">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 34px; border-radius: 8px;" />
        Atomicamente
      </a>
      
      <div style="display: flex; align-items: center; gap: 18px;">
        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 700; font-size: 0.9rem;">Painel Inicial</a>

        <!-- Badge de Ofensiva -->
<div style="display: flex; align-items: center; gap: 6px; background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.3); padding: 6px 12px; border-radius: 10px; font-weight: 800; color: #ea580c; font-size: 0.9rem;">
  🔥 <?php 
        // Puxa a ofensiva para exibir (adicione a query no topo do arquivo se necessário)
        $stmtStreak = $pdo->prepare("SELECT streak FROM users WHERE id = :uid");
        $stmtStreak->execute([':uid' => $_SESSION['user_id']]);
        echo $stmtStreak->fetchColumn() ?: 0;
     ?> Dias
</div>
          
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 14px; font-size: 0.88rem; border-radius: 10px; font-weight: 600; cursor: pointer;">🛠️ Modo</button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()"><span id="btn-tema-texto">🌙 Escuro</span></div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 16px; font-size: 0.9rem; border-radius: 10px; font-weight: 700; cursor: pointer;">
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

  <div class="container-dashboard">
    
    <div class="cabecalho-pagina">
      <h1 class="titulo-dash">Seu Boletim de Desempenho</h1>
      <p class="subtitulo-dash">Analise os seus dados, identifique pontos fracos e ajuste a sua rota de estudos.</p>
    </div>

    <!-- CARDS DE VISÃO GERAL -->
    <div class="stats-grid">
      <div class="stat-card card-destaque">
        <div class="stat-label">Taxa de Acerto Global</div>
        <div class="stat-value"><?php echo $taxa_acerto_geral; ?>%</div>
        <div class="stat-desc">Baseado no seu histórico geral</div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Exercícios Resolvidos</div>
        <div class="stat-value" style="color: var(--texto-principal);"><?php echo $total_resolvidas; ?></div>
        <div class="stat-desc">Questões finalizadas no total</div>
      </div>

      <div class="stat-card">
        <div class="stat-label">Acertos / Erros</div>
        <div class="stat-value" style="font-size: 2.2rem; margin-top: 15px;">
          <span style="color: #10b981;"><?php echo $total_acertos; ?></span> 
          <span style="color: var(--texto-secundario); font-weight: 400; font-size: 1.5rem;">/</span> 
          <span style="color: #ef4444;"><?php echo $total_erros; ?></span>
        </div>
        <div class="stat-desc">Balanço exato das suas marcações</div>
      </div>
    </div>

    <!-- DESEMPENHO DETALHADO POR MATÉRIA -->
    <div class="secao-detalhada">
      <h2 class="titulo-secao">📊 Desempenho por Frente de Estudo</h2>
      
      <?php if (empty($desempenho_frentes)): ?>
        <div class="empty-state">
          Você ainda não resolveu nenhum exercício. Comece os seus estudos na Sala de Aula para gerar os seus dados analíticos!
        </div>
      <?php else: ?>
        
        <?php foreach ($desempenho_frentes as $frente): ?>
          <?php 
            $taxa_frente = round(($frente['total_acertos'] / $frente['total_respondidas']) * 100); 
            
            // Lógica de cores baseada na performance
            $classe_cor = 'fill-bom'; // Padrão Verde
            if ($taxa_frente < 40) {
                $classe_cor = 'fill-ruim'; // Vermelho
            } elseif ($taxa_frente < 70) {
                $classe_cor = 'fill-medio'; // Laranja
            }
          ?>
          <div class="frente-row">
            <div class="frente-header">
              <span class="frente-nome"><?php echo htmlspecialchars($frente['frente_nome']); ?></span>
              <span class="frente-numeros">
                <b><?php echo $taxa_frente; ?>%</b> (<?php echo $frente['total_acertos']; ?>/<?php echo $frente['total_respondidas']; ?>)
              </span>
            </div>
            <div class="barra-bg">
              <div class="barra-fill <?php echo $classe_cor; ?>" style="width: <?php echo $taxa_frente; ?>%;"></div>
            </div>
          </div>
        <?php endforeach; ?>

      <?php endif; ?>
    </div>

  </div>

  <script>
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
