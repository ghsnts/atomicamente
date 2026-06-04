<?php
session_start();
require_once 'config.php';

// Proteção: Redireciona se não estiver logado ou se não vier via POST
if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: simulado.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$qtd_questoes = (int) $_POST['qtd_questoes'];
$ritmo_tempo = (float) $_POST['ritmo_tempo']; // Valor exato enviado pelo form
$topicos_selecionados = $_POST['topicos'] ?? [];

if (empty($topicos_selecionados)) {
    die("Nenhum tópico selecionado.");
}

try {
    $inQuery = implode(',', array_fill(0, count($topicos_selecionados), '?'));
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE subtopic_id IN ($inQuery) ORDER BY RAND() LIMIT ?");
    
    $params = $topicos_selecionados;
    $params[] = $qtd_questoes;
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $questoes_prova = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_questoes_geradas = count($questoes_prova);
    
    // Matemática exata do tempo
    $tempo_total_segundos = $ritmo_tempo > 0 ? (int)($total_questoes_geradas * $ritmo_tempo * 60) : 0; 

} catch (PDOException $e) {
    die("Erro ao gerar a prova: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simulado em Andamento | Atomicamente</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); margin: 0; padding-top: 75px; transition: background-color 0.3s, color 0.3s; }
    
    /* HEADER PREMIUM E ADAPTÁVEL AO TEMA */
    .header-foco { position: fixed; top: 0; left: 0; width: 100%; background-color: var(--bg-card); border-bottom: 1px solid var(--borda); padding: 12px 0; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: background-color 0.3s, border-color 0.3s; }
    .nav-foco { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
    .marca-foco { font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; color: var(--texto-principal); user-select: none; cursor: default; letter-spacing: -0.03em; }
    .badge-simulado { font-size: 0.7rem; background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 800; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3); }
    
    .botoes-header { display: flex; gap: 15px; align-items: center; }
    .btn-desistir { background: transparent; color: #ef4444; padding: 8px 16px; border-radius: 12px; font-weight: 700; text-decoration: none; border: 1px solid rgba(239, 68, 68, 0.3); transition: all 0.2s; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
    .btn-desistir:hover { background: #ef4444; color: white; }

    .cronometro-box { display: flex; align-items: center; gap: 10px; background: rgba(139, 92, 246, 0.1); border: 2px solid rgba(139, 92, 246, 0.3); color: var(--roxo-base); padding: 8px 18px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; letter-spacing: 1px; transition: all 0.3s; font-variant-numeric: tabular-nums; }
    .crono-zen { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); color: #10b981; }
    .crono-alerta { animation: piscarAlerta 1s infinite; background: #ef4444; color: white; border-color: #b91c1c; }
    @keyframes piscarAlerta { 0%, 100% { opacity: 1; } 50% { opacity: 0.8; } }

    /* LAYOUT PRINCIPAL: TRACKER NA ESQUERDA, PROVA NA DIREITA */
    .layout-prova { display: flex; max-width: 1000px; margin: 40px auto; gap: 40px; padding: 0 20px; align-items: flex-start; }
    
    /* A CADEIA DE BOLINHAS (PROGRESSO) */
    .sidebar-progresso { position: sticky; top: 110px; display: flex; flex-direction: column; align-items: center; width: 65px; background: var(--bg-card); padding: 25px 0; border-radius: 20px; border: 1px solid var(--borda); box-shadow: 0 4px 20px -5px rgba(0,0,0,0.03); max-height: calc(100vh - 150px); overflow-y: auto; overflow-x: hidden; scrollbar-width: none; transition: background-color 0.3s, border-color 0.3s; }
    .sidebar-progresso::-webkit-scrollbar { display: none; }
    .node-bolinha { width: 34px; height: 34px; border-radius: 50%; border: 2px solid var(--borda); display: flex; justify-content: center; align-items: center; font-weight: 800; font-size: 0.85rem; color: var(--texto-secundario); background: var(--bg-global); text-decoration: none; transition: all 0.3s; z-index: 2; flex-shrink: 0; }
    .node-linha { width: 3px; height: 18px; background: var(--borda); margin: -2px 0; z-index: 1; flex-shrink: 0; transition: all 0.3s; }
    
    /* Estados das bolinhas */
    .node-bolinha:hover { border-color: var(--roxo-base); color: var(--roxo-base); transform: scale(1.1); }
    .node-bolinha.respondida { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); border-color: transparent; color: white; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4); }
    .node-linha.ativa { background: var(--roxo-base); }

    /* CONTAINER DA PROVA */
    .container-questoes { flex: 1; width: 100%; min-width: 0; }
    .card-questao { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 24px; padding: 45px; margin-bottom: 40px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.03); scroll-margin-top: 100px; transition: background-color 0.3s, border-color 0.3s; }
    .badge-questao { font-size: 0.8rem; text-transform: uppercase; font-weight: 800; color: var(--roxo-base); letter-spacing: 0.05em; background: rgba(139, 92, 246, 0.1); padding: 6px 12px; border-radius: 8px; display: inline-block; margin-bottom: 25px; border: 1px solid rgba(139, 92, 246, 0.2); }
    .enunciado { font-size: 1.15rem; line-height: 1.7; color: var(--texto-principal); font-weight: 500; margin-bottom: 35px; text-align: justify; }
    
    .alternativas-lista { display: flex; flex-direction: column; gap: 12px; }
    .alt-label { display: flex; align-items: center; gap: 15px; padding: 16px 20px; border: 2px solid var(--borda); border-radius: 16px; background: var(--bg-global); cursor: pointer; transition: all 0.2s; user-select: none; }
    .alt-label:hover { border-color: var(--roxo-suave); background: rgba(139, 92, 246, 0.03); }
    .alt-label input[type="radio"] { display: none; }
    .alt-label input[type="radio"]:checked + .alt-letra { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border-color: transparent; }
    .alt-label:has(input[type="radio"]:checked) { border-color: var(--roxo-base); background: rgba(139, 92, 246, 0.05); box-shadow: 0 0 0 4px var(--roxo-suave); transform: translateX(5px); }
    .alt-letra { font-weight: 800; font-size: 1.1rem; color: var(--roxo-base); background: var(--bg-card); width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 2px solid var(--roxo-suave); flex-shrink: 0; transition: all 0.2s; }
    .alt-texto { font-size: 1rem; color: var(--texto-secundario); line-height: 1.5; font-weight: 500; }
    .alt-label:has(input[type="radio"]:checked) .alt-texto { font-weight: 700; color: var(--texto-principal); }
    
    /* BOTÃO DE ENTREGAR COM A IDENTIDADE DA MARCA */
    .btn-entregar { display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; text-align: center; background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; padding: 22px; font-size: 1.25rem; font-weight: 800; border-radius: 20px; cursor: pointer; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3); transition: all 0.3s ease; margin-bottom: 100px; letter-spacing: 0.03em; }
    .btn-entregar:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(139, 92, 246, 0.4); }
    .btn-entregar img { height: 26px; filter: brightness(0) invert(1); }

    .fab-container { position: fixed; bottom: 30px; right: 30px; display: flex; flex-direction: column; gap: 15px; align-items: flex-end; z-index: 9999; }
    .fab-btn { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4); font-size: 1.5rem; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: transform 0.2s; }
    .fab-btn:hover { transform: scale(1.1); }
    .fab-secundario { width: 50px; height: 50px; background: var(--bg-card); color: var(--texto-principal); border: 1px solid var(--borda); font-size: 1.2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

    /* JANELAS FLUTUANTES COM REDIMENSIONAMENTO FLUIDO ABSOLUTO */
    .janela-flutuante { position: fixed; background: var(--bg-global); border: 1px solid var(--borda); border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.4); z-index: 10000; display: none; flex-direction: column; overflow: hidden; transition: background-color 0.3s, border-color 0.3s; }
    .janela-maximizada { top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; border-radius: 0; }
    .janela-maximizada .resizer { display: none; }

    .resizer { position: absolute; z-index: 10; }
    .resizer-r { right: 0; top: 0; width: 10px; height: 100%; cursor: ew-resize; }
    .resizer-b { bottom: 0; left: 0; width: 100%; height: 10px; cursor: ns-resize; }
    .resizer-br { right: 0; bottom: 0; width: 20px; height: 20px; cursor: nwse-resize; }

    .janela-header { background: var(--bg-card); padding: 12px 20px; cursor: grab; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--borda); user-select: none; }
    .janela-header:active { cursor: grabbing; }
    
    .controles-janela { display: flex; gap: 8px; }
    .controles-janela button { background: var(--bg-global); border: 1px solid var(--borda); color: var(--texto-secundario); border-radius: 8px; width: 32px; height: 32px; font-size: 1rem; cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; }
    .controles-janela button:hover { color: var(--roxo-base); border-color: var(--roxo-base); background: rgba(139, 92, 246, 0.1); }
    .btn-fechar-janela:hover { color: white !important; border-color: #ef4444 !important; background: #ef4444 !important; }

    /* RASCUNHO */
    #janelaRascunho { top: 5vh; left: 5vw; width: 700px; height: 500px; min-width: 400px; min-height: 350px; }
    .ferramentas-desenho { background: var(--bg-card); padding: 12px 15px; display: flex; gap: 10px; align-items: center; border-bottom: 1px solid var(--borda); flex-wrap: wrap;}
    .btn-ferramenta { background: var(--bg-global); border: 1px solid var(--borda); padding: 8px 12px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--texto-principal); transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
    .btn-ferramenta:hover { background: var(--borda); }
    .btn-ferramenta.ativo { background: rgba(139, 92, 246, 0.1); border-color: var(--roxo-base); color: var(--roxo-base); }
    
    .cor-bolinha { width: 14px; height: 14px; border-radius: 50%; display: inline-block; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.2); }
    .btn-cor { padding: 8px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; background: transparent; transition: 0.2s; display: flex; justify-content: center; align-items: center; }
    .btn-cor:hover { background: var(--borda); }
    .btn-cor.ativo { border-color: var(--roxo-base); background: rgba(139, 92, 246, 0.1); }

    .area-canvas { flex-grow: 1; background-color: #ffffff; cursor: crosshair; position: relative; overflow: hidden; } 
    #canvasRascunho { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    .caderno-footer { background: var(--bg-card); padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--borda); }

    /* CALCULADORA */
    #janelaCalculadora { top: 10vh; right: 10vw; width: 380px; height: 500px; min-width: 320px; min-height: 480px; }
    .calc-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .calc-display { background: var(--bg-global); border: 1px solid var(--borda); height: 70px; min-height: 70px; border-radius: 16px; margin-bottom: 15px; text-align: right; font-size: 1.8rem; font-weight: 800; padding: 0 20px; display: flex; align-items: center; justify-content: flex-end; color: var(--texto-principal); overflow: hidden; letter-spacing: 1px; }
    .calc-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; flex-grow: 1; }
    .calc-btn { background: var(--bg-global); border: 1px solid var(--borda); border-radius: 12px; font-size: 1.1rem; font-weight: 700; color: var(--texto-principal); cursor: pointer; transition: all 0.1s; padding: 0; display: flex; align-items: center; justify-content: center;}
    .calc-btn:active { transform: scale(0.92); }
    .calc-btn.op { background: rgba(139, 92, 246, 0.05); color: var(--roxo-base); border-color: rgba(139, 92, 246, 0.2); }
    .calc-btn.ci { background: rgba(16, 185, 129, 0.05); color: #10b981; border-color: rgba(16, 185, 129, 0.2); font-size: 0.95rem; }
    .calc-btn.igual { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; grid-column: span 2; }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="header-foco">
    <div class="nav-foco">
      <div class="marca-foco">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 8px;" />
        Atomicamente <span class="badge-simulado">Simulado ENEM</span>
      </div>
      
      <div class="botoes-header">
        
        <div class="menu-dropdown">
          <button type="button" onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
            🛠️ Modo
          </button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()">
              <span id="btn-tema-texto">🌙 Modo Escuro</span>
            </div>
          </div>
        </div>

        <div class="cronometro-box <?php echo $tempo_total_segundos === 0 ? 'crono-zen' : ''; ?>" id="displayTempo">
          ⏳ 00:00:00
        </div>
        <a href="simulado.php" class="btn-desistir" onclick="return confirm('ATENÇÃO: Todo o seu progresso neste simulado será perdido e a sua nota será zero. Tem certeza que deseja desistir da prova?');">
          ✖ Desistir
        </a>
      </div>
    </div>
  </header>

  <form action="resultado.php" method="POST" id="formProva">
    <input type="hidden" name="tempo_configurado" value="<?php echo $tempo_total_segundos; ?>">
    <input type="hidden" name="tempo_gasto" id="inputTempoGasto" value="0">
    <input type="hidden" name="total_questoes_geradas" value="<?php echo $total_questoes_geradas; ?>">
    
    <div class="layout-prova">
        
      <?php if (empty($questoes_prova)): ?>
        <div class="container-questoes" style="text-align: center; margin-top: 50px;">
          <h2 style="color: var(--roxo-base);">Ops! Nenhuma questão encontrada.</h2>
          <a href="simulado.php" style="color: var(--roxo-base); font-weight: bold; text-decoration: none;">Voltar e reconfigurar</a>
        </div>
      <?php else: ?>
        
        <aside class="sidebar-progresso">
          <?php for ($i = 1; $i <= $total_questoes_geradas; $i++): ?>
            <a href="#questao-<?php echo $i; ?>" class="node-bolinha" id="node-<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php if ($i < $total_questoes_geradas): ?>
                <div class="node-linha" id="linha-<?php echo $i; ?>"></div>
            <?php endif; ?>
          <?php endfor; ?>
        </aside>

        <div class="container-questoes">
          <?php $contador = 1; foreach ($questoes_prova as $q): ?>
            <div class="card-questao" id="questao-<?php echo $contador; ?>" data-numero="<?php echo $contador; ?>">
              
              <div class="badge-questao">Questão <?php echo $contador; ?> de <?php echo $total_questoes_geradas; ?></div>
              <div class="enunciado"><?php echo nl2br(htmlspecialchars($q['enunciado'])); ?></div>
              
              <div class="alternativas-lista">
                  <?php 
                    $stmtAlt = $pdo->prepare("SELECT * FROM alternatives WHERE question_id = :qid ORDER BY letra ASC");
                    $stmtAlt->execute([':qid' => $q['id']]);
                    $alternativas = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
                  ?>
                  <?php foreach ($alternativas as $alt): ?>
                    <label class="alt-label">
                      <input type="radio" name="respostas[<?php echo $q['id']; ?>]" value="<?php echo $alt['letra']; ?>">
                      <div class="alt-letra"><?php echo $alt['letra']; ?></div>
                      <div class="alt-texto"><?php echo htmlspecialchars($alt['texto_alternativa']); ?></div>
                    </label>
                  <?php endforeach; ?>
              </div>

            </div>
          <?php $contador++; endforeach; ?>
          
          <button type="submit" class="btn-entregar" id="btnEntregarProva">
              <img src="assets/icone-simplificado.png" alt="Icone"> 
              Finalizar Simulado e Ver Desempenho
          </button>
        </div>

      <?php endif; ?>
    </div>
  </form>

  <div class="fab-container">
    <button type="button" class="fab-btn fab-secundario" onclick="abrirJanela('janelaCalculadora')" title="Calculadora Científica">🧮</button>
    <button type="button" class="fab-btn" onclick="abrirJanela('janelaRascunho')" title="Caderno de Rascunho">📝</button>
  </div>

  <div class="janela-flutuante" id="janelaRascunho">
    <div class="resizer resizer-r"></div>
    <div class="resizer resizer-b"></div>
    <div class="resizer resizer-br"></div>

    <div class="janela-header" id="headerRascunho">
      <div style="font-weight: 800; color: var(--texto-principal);">📝 Caderno Infinito</div>
      <div class="controles-janela">
        <button type="button" onclick="maximizarJanela('janelaRascunho')" title="Maximizar/Restaurar">🗖</button>
        <button type="button" class="btn-fechar-janela" onclick="fecharJanela('janelaRascunho')" title="Fechar">✖</button>
      </div>
    </div>
    
    <div class="ferramentas-desenho">
      <button type="button" class="btn-cor ativo" onclick="mudarCor('black', this)" title="Preto"><span class="cor-bolinha" style="background: black;"></span></button>
      <button type="button" class="btn-cor" onclick="mudarCor('#3b82f6', this)" title="Azul"><span class="cor-bolinha" style="background: #3b82f6;"></span></button>
      <button type="button" class="btn-cor" onclick="mudarCor('#ef4444', this)" title="Vermelho"><span class="cor-bolinha" style="background: #ef4444;"></span></button>
      
      <div style="width: 1px; height: 20px; background: var(--borda); margin: 0 5px;"></div>

      <button type="button" class="btn-ferramenta ativo" onclick="setTool('lapis', this)">✏️ Lápis</button>
      <button type="button" class="btn-ferramenta" onclick="setTool('linha', this)">📏 Linha</button>
      <button type="button" class="btn-ferramenta" onclick="setTool('retangulo', this)">🟦 Retângulo</button>
      <button type="button" class="btn-ferramenta" onclick="setTool('circulo', this)">⭕ Círculo</button>
      <button type="button" class="btn-ferramenta" onclick="setTool('borracha', this)">🧽 Borracha</button>
      
      <select id="espessura" class="btn-ferramenta" style="outline: none;">
        <option value="2">Fino</option>
        <option value="4" selected>Médio</option>
        <option value="8">Grosso</option>
      </select>

      <button type="button" class="btn-ferramenta" style="color: #ef4444; margin-left: auto;" onclick="limparPagina()">🗑️ Limpar</button>
    </div>
    
    <div class="area-canvas" id="canvasContainer">
      <div style="position: absolute; width: 100%; height: 100%; pointer-events: none; opacity: 0.3; background-image: linear-gradient(#9ca3af 1px, transparent 1px), linear-gradient(90deg, #9ca3af 1px, transparent 1px); background-size: 20px 20px;"></div>
      <canvas id="canvasRascunho"></canvas>
    </div>

    <div class="caderno-footer">
      <button type="button" class="btn-ferramenta" onclick="mudarPagina(-1)">⬅️ Ant.</button>
      <span style="font-weight: 700; font-size: 0.95rem; color: var(--texto-secundario);" id="contadorPagina">Pág 1 de 30</span>
      <button type="button" class="btn-ferramenta" onclick="mudarPagina(1)">Próx. ➡️</button>
    </div>
  </div>

  <div class="janela-flutuante" id="janelaCalculadora">
    <div class="resizer resizer-r"></div>
    <div class="resizer resizer-b"></div>
    <div class="resizer resizer-br"></div>

    <div class="janela-header" id="headerCalculadora">
      <div style="font-weight: 800; color: var(--texto-principal);">🧮 Calculadora</div>
      <div class="controles-janela">
        <button type="button" onclick="maximizarJanela('janelaCalculadora')" title="Maximizar/Restaurar">🗖</button>
        <button type="button" class="btn-fechar-janela" onclick="fecharJanela('janelaCalculadora')" title="Fechar">✖</button>
      </div>
    </div>
    
    <div class="calc-body">
      <div class="calc-display" id="calcDisplay">0</div>
      <div class="calc-grid">
        <button type="button" class="calc-btn ci" onclick="calcDigitar('sin(')">sin</button>
        <button type="button" class="calc-btn ci" onclick="calcDigitar('cos(')">cos</button>
        <button type="button" class="calc-btn ci" onclick="calcDigitar('tan(')">tan</button>
        <button type="button" class="calc-btn ci" onclick="calcDigitar('log(')">log</button>
        <button type="button" class="calc-btn op" style="color: #ef4444;" onclick="calcLimpar()">C</button>
        
        <button type="button" class="calc-btn ci" onclick="calcDigitar('√(')">√</button>
        <button type="button" class="calc-btn ci" onclick="calcDigitar('^')">xʸ</button>
        <button type="button" class="calc-btn op" onclick="calcDigitar('(')">(</button>
        <button type="button" class="calc-btn op" onclick="calcDigitar(')')">)</button>
        <button type="button" class="calc-btn op" onclick="calcDigitar('/')">÷</button>
        
        <button type="button" class="calc-btn ci" onclick="calcDigitar('π')">π</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('7')">7</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('8')">8</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('9')">9</button>
        <button type="button" class="calc-btn op" onclick="calcDigitar('*')">×</button>
        
        <button type="button" class="calc-btn ci" onclick="calcDigitar('e')">e</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('4')">4</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('5')">5</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('6')">6</button>
        <button type="button" class="calc-btn op" onclick="calcDigitar('-')">-</button>
        
        <button type="button" class="calc-btn op" onclick="calcDel()">⌫</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('1')">1</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('2')">2</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('3')">3</button>
        <button type="button" class="calc-btn op" onclick="calcDigitar('+')">+</button>
        
        <button type="button" class="calc-btn" onclick="calcDigitar('0')">0</button>
        <button type="button" class="calc-btn" onclick="calcDigitar('.')">.</button>
        <button type="button" class="calc-btn" onclick="calcDigitar(',')">,</button>
        <button type="button" class="calc-btn igual" onclick="calcCalcular()">=</button>
      </div>
    </div>
  </div>

  <script>
    // -------------------------------------------------------------
    // LOGICA DO DROPDOWN (MODO NOTURNO)
    // -------------------------------------------------------------
    function alternarDropdown(id) {
        document.querySelectorAll('.dropdown-conteudo').forEach(drop => {
            if(drop.id !== id) drop.classList.remove('mostrar');
        });
        document.getElementById(id).classList.toggle('mostrar');
    }
    window.addEventListener('click', function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => drop.classList.remove('mostrar'));
        }
    });

    // -------------------------------------------------------------
    // FUNCIONALIDADE 1: PERMITIR DESMARCAR UMA QUESTÃO CLICADA
    // -------------------------------------------------------------
    document.querySelectorAll('.alt-label').forEach(label => {
        label.addEventListener('click', function(e) {
            e.preventDefault(); // Previne o comportamento padrão do HTML de forçar a seleção
            const radio = this.querySelector('input[type="radio"]');
            
            if (radio.checked) {
                radio.checked = false; // Se estava marcada, desmarca
            } else {
                radio.checked = true; // Se não estava, marca
            }
            
            // Avisa o sistema que houve uma mudança para atualizar a bolinha na lateral
            radio.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });

    // -------------------------------------------------------------
    // PROGRESS TRACKER (CADEIA DE BOLINHAS DA BARRA LATERAL)
    // -------------------------------------------------------------
    document.querySelectorAll('.alt-label input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const questaoCard = this.closest('.card-questao');
            const num = questaoCard.dataset.numero;
            const bolinha = document.getElementById('node-' + num);
            const linha = document.getElementById('linha-' + num);
            
            // Verifica se QUALQUER rádio desta questão específica está checado
            const isQualquerMarcada = questaoCard.querySelector('input[type="radio"]:checked');
            
            if (isQualquerMarcada) {
                if (bolinha) bolinha.classList.add('respondida');
                if (linha) linha.classList.add('ativa');
            } else {
                if (bolinha) bolinha.classList.remove('respondida');
                if (linha) linha.classList.remove('ativa');
            }
        });
    });

    // -------------------------------------------------------------
    // FUNCIONALIDADE 2: ALERTA DE ENTREGA COM QUESTÕES EM BRANCO
    // -------------------------------------------------------------
    document.getElementById('formProva').addEventListener('submit', function(e) {
        let respondidas = 0;
        const totalQuestoes = <?php echo $total_questoes_geradas; ?>;
        
        // Conta quantas questões (cards) possuem pelo menos um input checado
        document.querySelectorAll('.card-questao').forEach(card => {
            if (card.querySelector('input[type="radio"]:checked')) {
                respondidas++;
            }
        });

        if (respondidas < totalQuestoes) {
            const emBranco = totalQuestoes - respondidas;
            const mensagem = `⚠️ ATENÇÃO: FALTAM QUESTÕES!\n\nVocê deixou ${emBranco} questão(ões) em branco.\n\nDeseja entregar a prova assim mesmo (e obter zero nelas) ou deseja CANCELAR para voltar à prova e respondê-las?`;
            
            if (!confirm(mensagem)) {
                e.preventDefault(); // O aluno clicou em Cancelar, aborta o envio
                return false;
            }
        }
        
        // Se estiver tudo respondido (ou ele aceitar enviar em branco), remove o alerta de fuga
        window.onbeforeunload = null; 
    });


    // -------------------------------------------------------------
    // LÓGICA DE JANELAS FLUTUANTES (RESIZE BLINDADO POR COORDENADAS)
    // -------------------------------------------------------------
    let zIndexCounter = 10000;

    function abrirJanela(id) {
        const win = document.getElementById(id);
        win.style.display = 'flex'; win.style.zIndex = ++zIndexCounter;
        if(id === 'janelaRascunho') setTimeout(redimensionarCanvas, 50);
    }
    function fecharJanela(id) { document.getElementById(id).style.display = 'none'; }
    function maximizarJanela(id) {
        const win = document.getElementById(id);
        win.classList.toggle('janela-maximizada');
        if(id === 'janelaRascunho') setTimeout(redimensionarCanvas, 50);
    }

    function tornarArrastavel(win, header) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        win.addEventListener('mousedown', () => { win.style.zIndex = ++zIndexCounter; });
        header.onmousedown = function(e) {
            if (win.classList.contains('janela-maximizada') || e.target.tagName === 'BUTTON') return;
            e.preventDefault(); pos3 = e.clientX; pos4 = e.clientY;
            document.onmouseup = fecharArrasto; document.onmousemove = iniciarArrasto;
        };
        function iniciarArrasto(e) {
            e.preventDefault(); pos1 = pos3 - e.clientX; pos2 = pos4 - e.clientY; pos3 = e.clientX; pos4 = e.clientY;
            win.style.top = (win.offsetTop - pos2) + "px"; win.style.left = (win.offsetLeft - pos1) + "px";
        }
        function fecharArrasto() { document.onmouseup = null; document.onmousemove = null; }
    }

    // NOVA LÓGICA DE RESIZE (100% à prova de bugs de escala)
    function tornarRedimensionavel(win) {
        const resizers = win.querySelectorAll('.resizer');
        let isResizing = false;
        
        for (let resizer of resizers) {
            resizer.addEventListener('mousedown', function(e) {
                if (win.classList.contains('janela-maximizada')) return;
                e.preventDefault();
                isResizing = true;
                const currentResizer = e.target;
                
                function mousemove(e) {
                    if (!isResizing) return;
                    const rect = win.getBoundingClientRect();
                    const minW = parseInt(window.getComputedStyle(win).minWidth) || 200;
                    const minH = parseInt(window.getComputedStyle(win).minHeight) || 200;
                    
                    if (currentResizer.classList.contains('resizer-br')) {
                        win.style.width = Math.max(e.clientX - rect.left, minW) + "px";
                        win.style.height = Math.max(e.clientY - rect.top, minH) + "px";
                    } else if (currentResizer.classList.contains('resizer-r')) {
                        win.style.width = Math.max(e.clientX - rect.left, minW) + "px";
                    } else if (currentResizer.classList.contains('resizer-b')) {
                        win.style.height = Math.max(e.clientY - rect.top, minH) + "px";
                    }
                }
                
                function mouseup() {
                    window.removeEventListener('mousemove', mousemove);
                    window.removeEventListener('mouseup', mouseup);
                    isResizing = false;
                    if(win.id === 'janelaRascunho') redimensionarCanvas();
                }

                window.addEventListener('mousemove', mousemove);
                window.addEventListener('mouseup', mouseup);
            });
        }
    }

    const calcWin = document.getElementById('janelaCalculadora');
    const rascWin = document.getElementById('janelaRascunho');
    
    tornarArrastavel(calcWin, document.getElementById('headerCalculadora'));
    tornarArrastavel(rascWin, document.getElementById('headerRascunho'));
    
    tornarRedimensionavel(calcWin);
    tornarRedimensionavel(rascWin);

    // -------------------------------------------------------------
    // CALCULADORA
    // -------------------------------------------------------------
    const calcDisplay = document.getElementById('calcDisplay');
    let calcValor = '';
    function calcDigitar(char) {
        if (calcDisplay.innerText === '0' || calcDisplay.innerText === 'Erro') calcValor = '';
        calcValor += char; calcDisplay.innerText = calcValor;
    }
    function calcLimpar() { calcValor = ''; calcDisplay.innerText = '0'; }
    function calcDel() {
        if (calcValor.length > 0) { calcValor = calcValor.slice(0, -1); calcDisplay.innerText = calcValor === '' ? '0' : calcValor; }
    }
    function calcCalcular() {
        try {
            let expressaoJS = calcValor.replace(/×/g, '*').replace(/÷/g, '/').replace(/,/g, '.').replace(/\^/g, '**').replace(/π/g, 'Math.PI').replace(/e/g, 'Math.E').replace(/sin\(/g, 'Math.sin(').replace(/cos\(/g, 'Math.cos(').replace(/tan\(/g, 'Math.tan(').replace(/log\(/g, 'Math.log10(').replace(/√\(/g, 'Math.sqrt(');
            const resultado = Function('"use strict";return (' + expressaoJS + ')')();
            if (isNaN(resultado) || !isFinite(resultado)) throw "Erro";
            calcValor = Number.isInteger(resultado) ? resultado.toString() : parseFloat(resultado.toFixed(6)).toString();
            calcDisplay.innerText = calcValor.replace(/\./g, ',');
        } catch(e) { calcDisplay.innerText = 'Erro'; calcValor = ''; }
    }

    // -------------------------------------------------------------
    // CADERNO DE RASCUNHO (MUITO MAIS PRECISO)
    // -------------------------------------------------------------
    const canvas = document.getElementById('canvasRascunho');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const container = document.getElementById('canvasContainer');
    
    let desenhando = false; let corAtual = 'black'; let ferramentaAtual = 'lapis';
    let paginaAtual = 1; const maxPaginas = 30;
    const paginasSalvas = new Array(maxPaginas + 1).fill(null);
    let startX, startY; let snapshotImgData;

    function redimensionarCanvas() {
        salvarPaginaAtual();
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;
        carregarPaginaAtual();
    }

    function mudarCor(cor, btnElement) {
        corAtual = cor;
        document.querySelectorAll('.btn-cor').forEach(btn => btn.classList.remove('ativo'));
        btnElement.classList.add('ativo');
        if(ferramentaAtual === 'borracha') { setTool('lapis', document.querySelectorAll('.btn-ferramenta')[0]); }
    }

    function setTool(tipo, btnElement) {
        ferramentaAtual = tipo;
        document.querySelectorAll('.btn-ferramenta').forEach(btn => { if(btn.innerText.includes('Lápis') || btn.innerText.includes('Borracha') || btn.innerText.includes('Linha') || btn.innerText.includes('Retângulo') || btn.innerText.includes('Círculo')) btn.classList.remove('ativo'); });
        if(btnElement) btnElement.classList.add('ativo');
    }

    function getMousePos(e) {
        const rect = canvas.getBoundingClientRect();
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    }

    canvas.addEventListener('mousedown', (e) => {
        desenhando = true;
        const pos = getMousePos(e);
        startX = pos.x; startY = pos.y;
        
        ctx.lineWidth = document.getElementById('espessura').value;
        ctx.lineCap = 'round'; ctx.lineJoin = 'round';
        
        if(ferramentaAtual === 'borracha') {
            ctx.globalCompositeOperation = "destination-out";
            ctx.lineWidth = ctx.lineWidth * 4; 
        } else {
            ctx.globalCompositeOperation = "source-over";
            ctx.strokeStyle = corAtual;
        }

        snapshotImgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        ctx.beginPath(); ctx.moveTo(startX, startY);
    });

    canvas.addEventListener('mousemove', (e) => {
        if (!desenhando) return;
        const pos = getMousePos(e);

        if (ferramentaAtual === 'lapis' || ferramentaAtual === 'borracha') {
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        } else {
            ctx.putImageData(snapshotImgData, 0, 0);
            ctx.beginPath();
            
            if (ferramentaAtual === 'linha') {
                ctx.moveTo(startX, startY); ctx.lineTo(pos.x, pos.y);
            } else if (ferramentaAtual === 'retangulo') {
                ctx.rect(startX, startY, pos.x - startX, pos.y - startY);
            } else if (ferramentaAtual === 'circulo') {
                const raio = Math.sqrt(Math.pow(pos.x - startX, 2) + Math.pow(pos.y - startY, 2));
                ctx.arc(startX, startY, raio, 0, 2 * Math.PI);
            }
            ctx.stroke();
        }
    });

    canvas.addEventListener('mouseup', () => desenhando = false);
    canvas.addEventListener('mouseleave', () => desenhando = false);

    function limparPagina() { if(confirm('Tem a certeza que deseja limpar esta página inteira?')) ctx.clearRect(0, 0, canvas.width, canvas.height); }
    
    function salvarPaginaAtual() { if (canvas.width > 0 && canvas.height > 0) paginasSalvas[paginaAtual] = canvas.toDataURL(); }
    function carregarPaginaAtual() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (paginasSalvas[paginaAtual]) { let img = new Image(); img.onload = function() { ctx.drawImage(img, 0, 0); }; img.src = paginasSalvas[paginaAtual]; }
    }
    function mudarPagina(direcao) {
        const novaPagina = paginaAtual + direcao;
        if (novaPagina >= 1 && novaPagina <= maxPaginas) {
            salvarPaginaAtual(); paginaAtual = novaPagina;
            document.getElementById('contadorPagina').innerText = `Pág ${paginaAtual} de ${maxPaginas}`;
            carregarPaginaAtual();
        }
    }

    // -------------------------------------------------------------
    // CRONÔMETRO PROTEGIDO
    // -------------------------------------------------------------
    let tempoConfigurado = <?php echo $tempo_total_segundos; ?>;
    let modoZen = tempoConfigurado === 0;
    let tempoAtual = modoZen ? 0 : tempoConfigurado;
    let tempoGasto = 0;
    const displayCrono = document.getElementById('displayTempo');

    function formatarTempo(segundos) {
        const h = Math.floor(segundos / 3600).toString().padStart(2, '0');
        const m = Math.floor((segundos % 3600) / 60).toString().padStart(2, '0');
        const s = (segundos % 60).toString().padStart(2, '0');
        return `⏳ ${h}:${m}:${s}`;
    }

    const intervalo = setInterval(() => {
        tempoGasto++; document.getElementById('inputTempoGasto').value = tempoGasto;
        if (modoZen) {
            tempoAtual++; displayCrono.innerText = formatarTempo(tempoAtual);
        } else {
            tempoAtual--; displayCrono.innerText = formatarTempo(tempoAtual);
            if (tempoAtual <= 300 && !displayCrono.classList.contains('crono-alerta')) displayCrono.classList.add('crono-alerta');
            if (tempoAtual <= 0) {
                clearInterval(intervalo); window.onbeforeunload = null;
                alert("O tempo esgotou! A prova será entregue automaticamente.");
                document.getElementById('formProva').submit();
            }
        }
    }, 1000);

    // Alerta Anti-fuga
    window.onbeforeunload = function() { return "Tens a certeza que queres sair? O teu progresso neste simulado será perdido."; };
    document.querySelector('.btn-desistir').addEventListener('click', function() { window.onbeforeunload = null; });
  </script>
</body>
</html>
