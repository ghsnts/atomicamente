<?php
session_start();
require_once 'config.php';

// Proteção: Garante que o aluno está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Matriz completa para gerar os cards dinamicamente no meio da tela
$matriz_cards = [
    // QUÍMICA GERAL
    ['slug' => 'modelos-atomicos',   'titulo' => 'Modelos Atómicos', 'cat' => 'Química Geral', 'icone' => '⚛️'],
    ['slug' => 'tabela-periodica',   'titulo' => 'Tabela Periódica', 'cat' => 'Química Geral', 'icone' => '📋'],
    ['slug' => 'ligacoes-quimicas',  'titulo' => 'Ligações Químicas', 'cat' => 'Química Geral', 'icone' => '🤝'],
    ['slug' => 'funcoes-inorganicas', 'titulo' => 'Funções Inorgânicas', 'cat' => 'Química Geral', 'icone' => '🧪'],
    ['slug' => 'estequiometria',     'titulo' => 'Estequiometria', 'cat' => 'Química Geral', 'icone' => '⚖️'],
    
    // FÍSICO-QUÍMICA
    ['slug' => 'solucoes',           'titulo' => 'Soluções & Conc.', 'cat' => 'Físico-Química', 'icone' => '💧'],
    ['slug' => 'termoquimica',       'titulo' => 'Termoquímica', 'cat' => 'Físico-Química', 'icone' => '🔥'],
    ['slug' => 'cinetica-quimica',   'titulo' => 'Cinética Química', 'cat' => 'Físico-Química', 'icone' => '⏱️'],
    ['slug' => 'equilibrio-quimico', 'titulo' => 'Equilíbrio Químico', 'cat' => 'Físico-Química', 'icone' => '🔄'],
    ['slug' => 'eletroquimica',      'titulo' => 'Eletroquímica', 'cat' => 'Físico-Química', 'icone' => '🔋'],

    // QUÍMICA ORGÂNICA
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
    /* COMPONENTES DO MENU EM GRADE (ESTILO DA CAPTURA DE TELA) */
    .hub-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }

    .card-hub-topico {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: white;
      border: 1.5px solid #d8e2ef; /* Borda suave como o print */
      border-radius: 14px;
      padding: 24px;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
      position: relative;
    }

    .card-hub-topico:hover {
      border-color: var(--roxo-vivo);
      box-shadow: 0 10px 20px rgba(109, 40, 217, 0.05);
      transform: translateY(-2px);
    }

    .card-hub-info {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .card-hub-tag {
      font-size: 0.7rem;
      text-transform: uppercase;
      font-weight: 700;
      color: #94a3b8;
      letter-spacing: 0.05em;
    }

    .card-hub-titulo {
      font-size: 1.05rem;
      font-weight: 600;
      color: var(--roxo-profundo);
    }

    .card-hub-icone {
      font-size: 1.8rem;
      opacity: 0.8;
      transition: transform 0.2s;
    }

    .card-hub-topico:hover .card-hub-icone {
      transform: scale(1.1);
      opacity: 1;
    }
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

  <main class="container" style="padding: 40px 0;">
    <div style="border-bottom: 1px solid var(--borda); padding-bottom: 20px; margin-bottom: 10px;">
      <h1 style="margin: 0; font-size: 1.8rem; color: var(--roxo-profundo); font-weight: 800;">O que vamos exercitar hoje?</h1>
      <p style="margin: 5px 0 0 0; color: var(--cinza-texto); font-size: 0.95rem;">Selecione uma das frentes da matriz de Química do ENEM para abrir a sala de aula.</p>
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

</body>
</html>
