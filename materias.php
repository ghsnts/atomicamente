<?php
session_start();
require_once 'config.php';

// Proteção: Garante que o aluno está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. Matriz da Árvore Pedagógica (Para alimentar a Barra Lateral)
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

// 2. Matriz de Cards (Para alimentar a Grade Central)
$matriz_cards = [
    ['slug' => 'modelos-atomicos',   'titulo' => 'Modelos Atómicos', 'cat' => 'Química Geral', 'icone' => '⚛️'],
    ['slug' => 'tabela-periodica',   'titulo' => 'Tabela Periódica', 'cat' => 'Química Geral', 'icone' => '📋'],
    ['slug' => 'ligacoes-quimicas',  'titulo' => 'Ligações Químicas', 'cat' => 'Química Geral', 'icone' => '🤝'],
    ['slug' => 'funcoes-inorganicas', 'titulo' => 'Funções Inorgânicas', 'cat' => 'Química Geral', 'icone' => '🧪'],
    ['slug' => 'estequiometria',     'titulo' => 'Estequiometria', 'cat' => 'Química Geral', 'icone' => '⚖️'],
    
    ['slug' => 'solucoes',           'titulo' => 'Soluções & Conc.', 'cat' => 'Físico-Química', 'icone' => '💧'],
    ['slug' => 'termoquimica',       'titulo' => 'Termoquímica', 'cat' => 'Físico-Química', 'icone' => '🔥'],
    ['slug' => 'cinetica-quimica',   'titulo' => 'Cinética Química', 'cat' => 'Físico-Química', 'icone' => '⏱️'],
    ['slug' => 'equilibrio-quimico', 'titulo' => 'Equilíbrio Químico', 'cat' => 'Físico-Química', 'icone' => '🔄'],
    ['slug' => 'eletroquimica',      'titulo' => 'Eletroquímica', 'cat' => 'Físico-Química', 'icone' => '🔋'],

    ['slug' => 'cadeias-carbonadas', 'titulo' => 'Cadeias Carbonadas', 'cat' => 'Química Orgânica', 'icone' => '⛓️'],
    ['slug' => 'funcoes-organicas',  'titulo' => 'Funções Orgânicas', 'cat' => 'Química Orgânica', 'icone' => '🌿'],
    ['slug' => 'isomeria',           'titulo' => 'Isomeria Espacial', 'cat' => 'Química Orgânica', 'icone' => '🪞'],
    ['slug' => 'reacoes-organicas',  'titulo' => 'Reações Orgânicas', 'cat' => 'Química Orgânica', 'icone' => '💥'],
    ['slug' => 'polimeros-bioq',     'titulo' => 'Polímeros & Bioquímica', 'cat' => 'Química Orgânica', 'icone' => '🧬'],
];
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
    /* DESIGN DE DIVISÃO (SIDEBAR + CONTEÚDO CENTRAL) */
    .hub-layout-grid {
      display: grid;
      grid-template-columns: 320px 1fr;
      min-height: calc(100vh - 65px);
    }

    /* ESTILO DA BARRA LATERAL (REUTILIZADO DO TOPICO.PHP) */
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

    /* CONTAINER DO CONTEÚDO DIREITO */
    .conteudo-hub {
      padding: 40px;
      background: var(--cinza-fundo);
      overflow-y: auto;
      max-height: calc(100vh - 65px);
    }

    /* A GRADE DE CARDS (ESTILO DO TEU PRINT) */
    .hub-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }
    .card-hub-topico {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      border: 1.5px solid #d8e2ef;
      border-radius: 14px;
      padding: 22px;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
    }
    .card-hub-topico:hover {
      border-color: var(--roxo-vivo);
      box-shadow: 0 10px 20px rgba(109, 40, 217, 0.04);
      transform: translateY(-2px);
    }
    .card-hub-info { display: flex; flex-direction: column; gap: 4px; }
    .card-hub-tag { font-size: 0.7rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; letter-spacing: 0.05em; }
    .card-hub-titulo { font-size: 1rem; font-weight: 600; color: var(--roxo-profundo); }
    .card-hub-icone { font-size: 1.6rem; opacity: 0.8; transition: transform 0.2s; }
    .card-hub-topico:hover .card-hub-icone { transform: scale(1.1); opacity: 1; }
  </style>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem">ENEM</span>
      </a>
      <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.9rem;">← Voltar ao Dashboard</a>
    </div>
  </header>

  <div class="hub-layout-grid">
    
    <nav class="sidebar-grade">
      <h2 style="font-size: 1.1rem; color: var(--roxo-profundo); margin-top:0; font-weight:700;">Grade Temática</h2>
      
      <?php foreach ($matriz_enem as $slug_topico => $dados): ?>
        <div class="titulo-categoria"><?php echo $dados['titulo']; ?></div>
        <?php foreach ($dados['subtopicos'] as $slug_sub => $nome_sub): ?>
          <a href="topico.php?id=<?php echo $slug_sub; ?>" class="link-subtopico">
             <?php echo $nome_sub; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

    <main class="conteudo-hub">
      <div style="border-bottom: 1px solid var(--borda); padding-bottom: 20px;">
        <h1 style="margin: 0; font-size: 1.8rem; color: var(--roxo-profundo); font-weight: 800;">O que vamos exercitar hoje?</h1>
        <p style="margin: 5px 0 0 0; color: var(--cinza-texto); font-size: 0.95rem;">Selecione uma das frentes abaixo ou navegue rapidamente pela barra lateral.</p>
      </div>

      <div class="hub-grid">
        <?php foreach ($matriz_cards as $card): ?>
          <a href="topico.php?id=<?php echo $card['slug']; ?>" class="card-hub-topico">
            <div class="card-hub-info">
              <span class="card-hub-tag"><?php echo $card['cat']; ?></span>
              <span class="card-hub-titulo"><?php echo $card['titulo']; ?></span>
            </div>
            <div class="card-hub-icone">
              <?php echo $card['icone']; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </main>

  </div>

</body>
</html>
