<?php
session_start();
require_once 'config.php';

// Proteção: Garante que o aluno está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ==========================================
// MAPEAMENTO COMPLETO DA MATRIZ DO ENEM
// ==========================================
$matriz_enem = [
    'quimica-geral' => [
        'titulo' => 'Química Geral',
        'subtopicos' => [
            'modelos-atomicos'  => 'Modelos Atómicos & Eletrosfera',
            'tabela-periodica'  => 'Propriedades Periódicas',
            'ligacoes-quimicas' => 'Ligações Químicas & Geometria',
            'funcoes-inorganicas'=> 'Funções Inorgânicas (Ácidos, Bases...)',
            'estequiometria'    => 'Estequiometria & Cálculos Químicos'
        ]
    ],
    'fisico-quimica' => [
        'titulo' => 'Físico-Química',
        'subtopicos' => [
            'solucoes'          => 'Soluções & Concentrações',
            'termoquimica'      => 'Termoquímica (Entalpia)',
            'cinetica-quimica'  => 'Cinética & Velocidade de Reação',
            'equilibrio-quimico'=> 'Equilíbrio Químico & pH',
            'eletroquimica'     => 'Eletroquímica (Pilhas e Eletrólise)'
        ]
    ],
    'quimica-organica' => [
        'titulo' => 'Química Orgânica',
        'subtopicos' => [
            'cadeias-carbonadas'=> 'Introdução & Classificação de Cadeias',
            'funcoes-organicas' => 'Funções Orgânicas (Álcool, Éster...)',
            'isomeria'          => 'Isomeria Plana e Espacial',
            'reacoes-organicas' => 'Reações Orgânicas de Adição/Substituição',
            'polimeros-bioq'    => 'Polímeros & Bioquímica do ENEM'
        ]
    ]
];

// Identifica qual o subtópico ativo vindo da URL (padrão: modelos-atomicos)
$subtopico_atual = isset($_GET['id']) ? $_GET['id'] : 'modelos-atomicos';

// Descobre a qual tópico principal este subtópico pertence
$topico_atual_slug = 'quimica-geral';
$nome_subtopico_atual = 'Modelos Atómicos & Eletrosfera';

foreach ($matriz_enem as $slug_topico => $dados) {
    if (array_key_exists($subtopico_atual, $dados['subtopicos'])) {
        $topico_atual_slug = $slug_topico;
        $nome_subtopico_atual = $dados['subtopicos'][$subtopico_atual];
        break;
    }
}

// ==========================================
// BUSCA DINÂMICA DE CONTEÚDO & QUESTÕES
// ==========================================
// Exemplo de dados mockados para o conteúdo caso não ache no banco imediatamente
$conteudo_texto = "Bem-vindo ao módulo de <strong>$nome_subtopico_atual</strong>. Assista à videoaula acima e teste os seus conhecimentos nos exercícios práticos.";
$video_url = "https://www.youtube.com/embed/dQw4w9WgXcQ"; // Link placeholder

// Aqui buscamos a questão vinculada a este subtópico
try {
    // Busca a primeira questão deste subtopico
    $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE subtopic_id = (SELECT id FROM subtopics WHERE slug = :slug LIMIT 1) LIMIT 1");
    $stmtQ->execute([':slug' => $subtopico_atual]);
    $questao = $stmtQ->fetch();

    $alternativas = [];
    if ($questao) {
        $stmtA = $pdo->prepare("SELECT * FROM alternatives WHERE question_id = :qid");
        $stmtA->execute([':qid' => $questao['id']]);
        $alternativas = $stmtA->fetchAll();
    }
} catch (PDOException $e) {
    // Fallback silencioso para não quebrar o layout visual
    $questao = null;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $nome_subtopico_atual; ?> | Aula</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    /* LAYOUT DIVIDIDO: MENU LATERAL + CONTEÚDO */
    .sala-aula-grid {
      display: grid;
      grid-template-columns: 320px 1fr;
      min-height: calc(100vh - 65px);
    }
    
    /* MENU LATERAL (SIDEBAR) */
    .sidebar-grade {
      background: white;
      border-right: 1px solid var(--borda);
      padding: 25px 20px;
      overflow-y: auto;
      max-height: calc(100vh - 65px);
    }
    .titulo-categoria {
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--cinza-texto);
      margin: 20px 0 10px 0;
      font-weight: 700;
    }
    .link-subtopico {
      display: block;
      padding: 10px 12px;
      color: #334155;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
      border-radius: 8px;
      margin-bottom: 4px;
      transition: all 0.2s;
    }
    .link-subtopico:hover {
      background: var(--roxo-suave);
      color: var(--roxo-base);
    }
    .link-subtopico.ativo {
      background: var(--roxo-base);
      color: white;
      font-weight: 600;
    }

    /* ÁREA DE CONTEÚDO PRINCIPAL */
    .conteudo-aula {
      padding: 40px;
      background: var(--cinza-fundo);
      overflow-y: auto;
    }

    /* ABAS INTERATIVAS (TABS) */
    .abas-container {
      display: flex;
      border-bottom: 2px solid var(--borda);
      margin-bottom: 30px;
      gap: 8px;
    }
    .aba-btn {
      padding: 12px 24px;
      background: none;
      border: none;
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--cinza-texto);
      cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-bottom: -2px;
      transition: all 0.2s;
    }
    .aba-btn:hover { color: var(--roxo-base); }
    .aba-btn.ativa {
      color: var(--roxo-base);
      border-bottom: 2px solid var(--roxo-base);
    }
    .painel-conteudo { display: none; }
    .painel-conteudo.ativo { display: block; }

    /* ESTILIZAÇÃO DO PLAYER DE VÍDEO */
    .video-wrapper {
      position: relative;
      padding-bottom: 56.25%; /* Proporção 16:9 */
      height: 0;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      margin-bottom: 25px;
      border: 1px solid var(--borda);
    }
    .video-wrapper iframe {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
    }

    /* CARDS DE ALTERNATIVAS */
    .card-pergunta {
      background: white;
      padding: 30px;
      border-radius: 16px;
      border: 1px solid var(--borda);
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
    }
    .opcao-container {
      display: block;
      position: relative;
      padding: 16px 16px 16px 50px;
      margin-bottom: 12px;
      cursor: pointer;
      border: 1px solid var(--borda);
      border-radius: 10px;
      font-size: 0.95rem;
      user-select: none;
    }
    .opcao-container input { position: absolute; opacity: 0; cursor: pointer; }
    .checkmark {
      position: absolute;
      top: 15px; left: 16px;
      height: 20px; width: 20px;
      background-color: #f1f5f9;
      border: 1px solid var(--borda);
      border-radius: 50%;
    }
    .opcao-container:hover .checkmark { border-color: var(--roxo-vivo); }
    .opcao-container input:checked ~ .checkmark {
      background-color: var(--roxo-base);
      border-color: var(--roxo-base);
    }
    .checkmark:after {
      content: ""; position: absolute; display: none;
      top: 5px; left: 5px; width: 8px; height: 8px;
      border-radius: 50%; background: white;
    }
    .opcao-container input:checked ~ .checkmark:after { display: block; }
  </style>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem">ENEM</span>
      </a>
      <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.9rem;">← Voltar ao Painel</a>
    </div>
  </header>

  <div class="sala-aula-grid">
    
    <nav class="sidebar-grade">
      <h2 style="font-size: 1.1rem; color: var(--roxo-profundo); margin-top:0; font-weight:700;">Grade Temática</h2>
      
      <?php foreach ($matriz_enem as $slug_topico => $dados): ?>
        <div class="titulo-categoria"><?php echo $dados['titulo']; ?></div>
        <?php foreach ($dados['subtopicos'] as $slug_sub => $nome_sub): ?>
          <a href="topico.php?id=<?php echo $slug_sub; ?>" 
             class="link-subtopico <?php echo ($subtopico_atual === $slug_sub) ? 'ativo' : ''; ?>">
             <?php echo $nome_sub; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <main class="conteudo-aula">
      <div style="margin-bottom: 25px;">
        <span style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700; color: var(--roxo-vivo);">
          <?php echo $matriz_enem[$topico_atual_slug]['titulo']; ?>
        </span>
        <h1 style="margin: 5px 0 0 0; font-size: 1.8rem; color: var(--roxo-profundo); font-weight: 800;">
          <?php echo $nome_subtopico_atual; ?>
        </h1>
      </div>

      <div class="abas-container">
        <button class="aba-btn ativa" onclick="mudarAba(event, 'painel-teoria')">📚 Teoria & Aula</button>
        <button class="aba-btn" onclick="mudarAba(event, 'painel-exercicios')">📝 Exercícios Fixação</button>
      </div>

      <div id="painel-teoria" class="painel-conteudo ativo">
        <div class="video-wrapper">
          <iframe src="<?php echo $video_url; ?>" title="Videoaula ENEM" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        <div class="card-pergunta" style="line-height: 1.7; color: #334155;">
          <h3 style="margin-top:0; color: var(--roxo-profundo);">Resumo Teórico</h3>
          <p><?php echo $conteudo_texto; ?></p>
        </div>
      </div>

      <div id="painel-exercicios" class="painel-conteudo">
        <?php if ($questao): ?>
          <div class="card-pergunta" id="card-questao-<?php echo $questao['id']; ?>">
            <span class="badge-enem" style="background: var(--roxo-suave); color: var(--roxo-base); padding: 4px 8px; margin-bottom: 15px; display: inline-block;">QUESTÃO DE FIXAÇÃO</span>
            <p style="font-size: 1.05rem; line-height: 1.6; font-weight: 500; color: var(--roxo-profundo); margin-top: 0;">
              <?php echo nl2br(htmlspecialchars($questao['statement'])); ?>
            </p>

            <form style="margin-top: 25px;">
              <?php foreach ($alternativas as $alt): ?>
                <label class="opcao-container opcao-radio-card" id="label-alt-<?php echo $alt['id']; ?>">
                  <input type="radio" name="questao_opcao" value="<?php echo $alt['id']; ?>" 
                         data-correct="<?php echo $alt['is_correct']; ?>"
                         onclick="verificarResposta(<?php echo $questao['id']; ?>, <?php echo $alt['id']; ?>, <?php echo $alt['is_correct']; ?>)">
                  <span class="checkmark"></span>
                  <strong><?php echo htmlspecialchars($alt['letter']); ?>)</strong> <?php echo htmlspecialchars($alt['text_content']); ?>
                </label>
              <?php endforeach; ?>
            </form>
          </div>
        <?php else: ?>
          <div class="card-pergunta" style="text-align: center; padding: 40px;">
            <span style="font-size: 2.5rem;">🚧</span>
            <h3 style="color: var(--roxo-profundo); margin-top: 15px;">Módulo em construção</h3>
            <p style="color: var(--cinza-texto); margin: 0;">Ainda não existem questões cadastradas para este subtópico no banco de dados.</p>
          </div>
        <?php endif; ?>
      </div>

    </main>
  </div>

  <script>
    function mudarAba(evt, idPainel) {
      const painis = document.getElementsByClassName("painel-conteudo");
      for (let i = 0; i < painis.length; i++) {
        painis[i].classList.remove("ativo");
      }
      const botoes = document.getElementsByClassName("aba-btn");
      for (let i = 0; i < botoes.length; i++) {
        botoes[i].classList.remove("ativa");
      }
      document.getElementById(idPainel).classList.add("ativo");
      evt.currentTarget.classList.add("ativa");
    }
  </script>
  
  <script src="js/plataforma.js"></script>
</body>
</html>
