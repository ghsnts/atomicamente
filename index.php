<?php 
// Inicia a sessão com segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$logado = isset($_SESSION['user_id']);
$primeiro_nome = 'Estudante';
if ($logado) {
    $primeiro_nome = explode(' ', trim($_SESSION['user_nome'] ?? 'Aluno'))[0];
}

// =========================================================================================
// BUSCA DINÂMICA DO "MINI DESAFIO ENEM"
// =========================================================================================
$desafio_enunciado = "Qual das misturas abaixo é classificada como um sistema Homogêneo?"; // Fallback
$alts_desafio = [];
$letra_correta_desafio = 'B'; // Fallback

try {
    // Busca 1 questão aleatória do banco
    $stmtDesafio = $pdo->query("SELECT id, enunciado FROM questions ORDER BY RAND() LIMIT 1");
    $questao_rand = $stmtDesafio->fetch(PDO::FETCH_ASSOC);

    if ($questao_rand) {
        $desafio_enunciado = $questao_rand['enunciado'];
        
        // Busca as alternativas dessa questão
        $stmtAlts = $pdo->prepare("SELECT letra, texto_alternativa, eh_correta FROM alternatives WHERE question_id = ? ORDER BY letra ASC");
        $stmtAlts->execute([$questao_rand['id']]);
        $alts_desafio = $stmtAlts->fetchAll(PDO::FETCH_ASSOC);
        
        // Acha qual é a correta para passar pro JavaScript
        foreach ($alts_desafio as $alt) {
            if ($alt['eh_correta'] == 1) {
                $letra_correta_desafio = $alt['letra'];
            }
        }
    }
} catch (Exception $e) {
    // Mantém fallback nativo em caso de erro de conexão
}
?>
<!DOCTYPE html>
<html lang="pt-BR" style="scroll-behavior: smooth;">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Atomicamente | Domine a Química para o ENEM</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  
  <style>
    /* ================= VARIÁVEIS DE TEMA (MODO CLARO E ESCURO) ================= */
    :root {
      --roxo-base: #7c3aed;
      --roxo-escuro: #4c1d95;
      --roxo-claro: #ede9fe;
      --texto-principal: #1e293b;
      --texto-secundario: #64748b;
      --bg-global: #f8fafc;
      --bg-card: #ffffff;
      --borda: #e2e8f0;
      --laranja-enem: #ea580c;
      --nav-bg: rgba(255, 255, 255, 0.85);
    }

    [data-theme="dark"] {
      --roxo-base: #8b5cf6;
      --roxo-escuro: #c4b5fd;
      --roxo-claro: #2e1065;
      --texto-principal: #f8fafc;
      --texto-secundario: #94a3b8;
      --bg-global: #0f172a;
      --bg-card: #1e293b;
      --borda: #334155;
      --nav-bg: rgba(15, 23, 42, 0.85);
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-global);
      color: var(--texto-principal);
      margin: 0; padding: 0; overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* ================= CABEÇALHO (NAVBAR PREMIUM) ================= */
    .header-site {
      position: fixed; top: 0; width: 100%;
      background: var(--nav-bg); backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--borda);
      z-index: 1000; transition: all 0.3s ease;
    }
    .nav-container {
      max-width: 1200px; margin: 0 auto; padding: 12px 20px;
      display: flex; justify-content: space-between; align-items: center;
    }
    .marca {
      display: flex; align-items: center; gap: 10px;
      font-weight: 800; font-size: 1.3rem; color: var(--texto-principal);
      text-decoration: none; letter-spacing: -0.03em;
    }
    .marca img { height: 34px; border-radius: 8px; }
    
    .nav-links { display: flex; gap: 25px; align-items: center; }
    .nav-links a {
      text-decoration: none; color: var(--texto-secundario);
      font-weight: 600; font-size: 0.95rem; transition: color 0.2s;
    }
    .nav-links a:hover { color: var(--roxo-base); }
    
    /* DROPDOWNS DO HEADER */
    .menu-dropdown { position: relative; display: inline-block; }
    .dropdown-conteudo {
        display: none; position: absolute; right: 0; top: 110%;
        background-color: var(--bg-card); min-width: 180px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 12px;
        border: 1px solid var(--borda); overflow: hidden; z-index: 200;
        animation: dropAnim 0.2s ease forwards;
    }
    @keyframes dropAnim { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .dropdown-conteudo.mostrar { display: block; }
    .dropdown-item {
        color: var(--texto-principal); padding: 12px 16px; text-decoration: none;
        display: block; font-size: 0.9rem; font-weight: 600; transition: background 0.2s; cursor: pointer;
    }
    .dropdown-item:hover { background-color: var(--bg-global); color: var(--roxo-base); }
    .dropdown-divisor { height: 1px; background-color: var(--borda); margin: 4px 0; }
    .sair { color: #ef4444; } .sair:hover { color: #dc2626; background-color: rgba(239, 68, 68, 0.05); }

    .btn-entrar { color: var(--texto-principal); font-weight: 700; text-decoration: none; transition: color 0.2s; }
    .btn-entrar:hover { color: var(--roxo-base); }
    .btn-comecar {
      background: linear-gradient(135deg, var(--roxo-base), #6d28d9); color: white !important;
      padding: 10px 24px; border-radius: 10px; font-weight: 700; text-decoration: none;
      box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3); transition: transform 0.2s;
    }
    .btn-comecar:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4); }

    /* ================= HERO SECTION ================= */
    .hero {
      padding: 160px 20px 100px 20px; max-width: 1200px; margin: 0 auto;
      text-align: center; display: flex; flex-direction: column; align-items: center;
    }
    .hero-badge {
      background: rgba(234, 88, 12, 0.1); color: var(--laranja-enem);
      padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 800;
      text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px;
      border: 1px solid rgba(234, 88, 12, 0.2);
    }
    .hero h1 {
      font-size: 4.2rem; font-weight: 900; letter-spacing: -0.04em;
      margin: 0 0 20px 0; line-height: 1.1; color: var(--texto-principal); max-width: 900px; transition: color 0.3s ease;
    }
    .hero h1 span { background: linear-gradient(135deg, var(--roxo-base), #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .hero p { font-size: 1.2rem; color: var(--texto-secundario); max-width: 700px; line-height: 1.6; margin: 0 0 40px 0; transition: color 0.3s ease; }
    .hero-buttons { display: flex; gap: 15px; justify-content: center; }

    /* ================= FEATURES ================= */
    .features { background: var(--bg-card); padding: 100px 20px; border-top: 1px solid var(--borda); border-bottom: 1px solid var(--borda); transition: background-color 0.3s ease;}
    .features-container { max-width: 1200px; margin: 0 auto; }
    .section-title { text-align: center; margin-bottom: 60px; }
    .section-title h2 { font-size: 2.5rem; font-weight: 800; margin: 0 0 15px 0; letter-spacing: -0.03em;}
    .section-title p { color: var(--texto-secundario); font-size: 1.1rem; margin: 0; }
    
    .grid-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
    .card-feature { background: var(--bg-global); padding: 40px 30px; border-radius: 24px; border: 1px solid var(--borda); transition: all 0.3s ease; }
    .card-feature:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); border-color: var(--roxo-base); background: var(--bg-card);}
    .feature-icon { font-size: 2.5rem; margin-bottom: 20px; display: inline-block; background: var(--roxo-claro); padding: 15px; border-radius: 16px; }
    .card-feature h3 { font-size: 1.3rem; font-weight: 800; margin: 0 0 10px 0; }
    .card-feature p { color: var(--texto-secundario); line-height: 1.5; margin: 0; font-size: 0.95rem; }

    /* ================= SEÇÃO INTERATIVOS ================= */
    .interativos-section { padding: 100px 20px; max-width: 1200px; margin: 0 auto; }
    
    /* 1. Simulador de pH */
    .simulador-box {
      background: linear-gradient(145deg, #1e1b4b, #3b0764); border-radius: 30px; padding: 60px; color: white; margin-bottom: 50px;
      box-shadow: 0 20px 50px rgba(76, 29, 149, 0.2); display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;
    }
    .simulador-info h2 { font-size: 2.2rem; font-weight: 800; margin: 0 0 15px 0; }
    .simulador-info p { color: #cbd5e1; font-size: 1.05rem; line-height: 1.6; margin: 0 0 30px 0; }
    .controle-ph { background: white; padding: 30px; border-radius: 20px; text-align: center; color: #1e293b; }
    .ph-display { font-size: 2.5rem; font-weight: 900; color: #4c1d95; margin-bottom: 5px; }
    .ph-sub { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; }
    input[type=range] { width: 100%; height: 12px; border-radius: 6px; outline: none; -webkit-appearance: none; background: linear-gradient(to right, #ef4444, #f59e0b, #10b981, #3b82f6, #1e3a8a); }
    input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 24px; height: 24px; border-radius: 50%; background: white; border: 3px solid var(--roxo-base); cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2); }

    /* 2. Flashcards 3D */
    .grid-flashcards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 80px;}
    .flip-card { background-color: transparent; height: 220px; perspective: 1000px; }
    .flip-card-inner { position: relative; width: 100%; height: 100%; text-align: center; transition: transform 0.6s; transform-style: preserve-3d; cursor: pointer; }
    .flip-card:hover .flip-card-inner { transform: rotateY(180deg); }
    .flip-card-front, .flip-card-back { position: absolute; width: 100%; height: 100%; -webkit-backface-visibility: hidden; backface-visibility: hidden; border-radius: 24px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 25px; box-sizing: border-box; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .flip-card-front { background-color: var(--bg-card); color: var(--texto-principal); border: 1px solid var(--borda); }
    .flip-card-front h4 { font-size: 3.5rem; margin: 0; color: var(--roxo-base); font-weight: 900; line-height: 1; }
    .flip-card-front p { font-size: 0.9rem; color: var(--texto-secundario); font-weight: 700; text-transform: uppercase; margin-top: 10px; letter-spacing: 0.05em; }
    .flip-card-back { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; transform: rotateY(180deg); border: 1px solid rgba(255,255,255,0.2); }
    .flip-card-back h4 { font-size: 1.4rem; margin: 0 0 10px 0; font-weight: 800; }
    .flip-card-back p { font-size: 0.95rem; line-height: 1.5; margin: 0; opacity: 0.9; font-weight: 500;}

    /* 3. Galeria de Notáveis (Imagens Locais em Assets) */
    .notaveis-container { display: flex; height: 400px; gap: 14px; margin-bottom: 80px;}
    .notavel-card {
        flex: 1; border-radius: 24px; overflow: hidden;
        transition: flex 0.6s cubic-bezier(0.25, 0.8, 0.25, 1), border-color 0.3s;
        cursor: pointer; position: relative; border: 1px solid var(--borda);
        display: flex; flex-direction: column; justify-content: flex-end; padding: 25px;
        background-size: cover; background-position: center center; background-repeat: no-repeat;
        background-color: #1e1b4b; /* Fallback em caso de erro na imagem */
    }
    .notavel-card::before { 
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
        background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.5) 50%, rgba(15,23,42,0.2) 100%); 
        z-index: 1; transition: opacity 0.3s;
    }
    .notavel-card:hover { flex: 4; border-color: var(--roxo-base); }
    .notavel-info { position: relative; z-index: 2; color: white; text-align: left; }
    .notavel-nome { font-weight: 800; font-size: 1.4rem; margin: 0; white-space: nowrap; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
    .notavel-desc { font-size: 0.95rem; opacity: 0; max-height: 0; transition: all 0.5s ease; margin-top: 10px; line-height: 1.6; color: #e2e8f0; }
    .notavel-card:hover .notavel-desc { opacity: 1; max-height: 150px; }

    /* CARREGANDO IMAGENS LOCAIS DA PASTA ASSETS */
    .bg-curie     { background-image: url('assets/curie.jpg'); }
    .bg-lavoisier { background-image: url('assets/lavoisier.jpg'); }
    .bg-mendeleev { background-image: url('assets/mendeleev.jpg'); }
    .bg-bohr      { background-image: url('assets/bohr.jpg'); }
    .bg-pauling   { background-image: url('assets/pauling.jpg'); }

    /* 4. Mini Desafio ENEM Dinâmico */
    .desafio-box { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 30px; padding: 50px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
    .desafio-pergunta { font-size: 1.25rem; font-weight: 600; line-height: 1.6; margin-bottom: 30px; color: var(--texto-principal); max-width: 800px; margin-inline: auto; text-align: justify; }
    .desafio-opcoes { display: grid; grid-template-columns: 1fr; gap: 12px; max-width: 800px; margin: 0 auto; }
    .btn-opcao {
        background: var(--bg-global); border: 2px solid var(--borda); color: var(--texto-principal); text-align: left;
        padding: 18px 25px; border-radius: 16px; font-weight: 500; font-size: 1.05rem; cursor: pointer; transition: all 0.2s; line-height: 1.4;
    }
    .btn-opcao:hover { border-color: var(--roxo-base); background: var(--roxo-claro); transform: translateX(5px); }
    .btn-opcao.correta { background: #10b981 !important; color: white !important; border-color: #059669 !important; }
    .btn-opcao.errada { background: #ef4444 !important; color: white !important; border-color: #dc2626 !important; }
    
    /* REVISÃO E COMPORTAMENTO PREMIUM DO FEEDBACK (Agora com alinhamento e botões polidos) */
    #feedbackDesafio { 
        margin-top: 30px; display: none; padding: 30px; border-radius: 20px; 
        max-width: 800px; margin-inline: auto; text-align: left; line-height: 1.6;
        animation: dropAnim 0.3s ease-out forwards;
    }
    .container-botoes-feedback {
        display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap; justify-content: flex-start; align-items: center;
    }
    .btn-cta-desafio { 
        display: inline-flex; align-items: center; justify-content: center;
        padding: 14px 28px; background: var(--roxo-base); color: white !important; 
        text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 1rem;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25); transition: all 0.2s ease;
    }
    .btn-cta-desafio:hover { 
        transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124, 58, 237, 0.4); 
    }
    .btn-cta-secundario {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 14px 28px; background: var(--bg-card); color: var(--texto-principal) !important;
        border: 2px solid var(--borda); text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 1rem;
        transition: all 0.2s ease;
    }
    .btn-cta-secundario:hover {
        background: var(--bg-global); border-color: var(--texto-secundario);
    }

    /* ================= SOBRE NÓS (TEASER) ================= */
    .sobre-teaser { background: var(--roxo-claro); padding: 100px 20px; text-align: center; transition: background-color 0.3s ease;}
    .sobre-container { max-width: 800px; margin: 0 auto; }
    .badge-ifsul { background: var(--bg-card); color: var(--roxo-base); padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; display: inline-block; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border: 1px solid var(--borda);}
    .sobre-container h2 { font-size: 2.5rem; font-weight: 800; margin: 0 0 20px 0; color: var(--texto-principal); letter-spacing: -0.02em;}
    .sobre-container p { font-size: 1.15rem; color: var(--texto-secundario); line-height: 1.6; margin: 0 0 35px 0; }

    /* ================= FOOTER ================= */
    .footer { background: var(--bg-card); border-top: 1px solid var(--borda); padding: 60px 20px; transition: background-color 0.3s ease;}
    .footer-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 40px; }
    .footer-col h4 { font-weight: 800; margin: 0 0 20px 0; color: var(--texto-principal); }
    .footer-col a { display: block; color: var(--texto-secundario); text-decoration: none; margin-bottom: 12px; transition: color 0.2s; font-weight: 500;}
    .footer-col a:hover { color: var(--roxo-base); }
    
    @media (max-width: 900px) {
        .nav-links { display: none; } 
        .hero h1 { font-size: 2.8rem; }
        .simulador-box { grid-template-columns: 1fr; padding: 40px 20px; }
        .notaveis-container { flex-direction: column; height: auto; }
        .notavel-card { padding: 40px 20px; height: 120px; }
        .notavel-card:hover { height: 250px; }
        .notavel-card .notavel-desc { opacity: 1; max-height: none; margin-top: 5px;}
        .container-botoes-feedback { flex-direction: column; width: 100%; }
        .btn-cta-desafio, .btn-cta-secundario { width: 100%; text-align: center; }
    }
  </style>
</head>
<body>

  <header class="header-site">
    <div class="nav-container">
      <a href="index.php" class="marca">
        <img src="assets/icone-simplificado.png" alt="Logo" />
        Atomicamente
      </a>
      
      <nav class="nav-links">
        <a href="#plataforma">A Plataforma</a>
        <a href="#interativos">Interativos</a>
        <a href="sobre.php">Sobre Nós</a>
        <a href="contato.php">Contato</a>
      </nav>

      <div class="nav-actions">
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 10px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
            🛠️ Configs
          </button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()">
              <span id="btn-tema-texto">🌙 Modo Escuro</span>
            </div>
          </div>
        </div>

        <?php if ($logado): ?>
            <div class="menu-dropdown">
              <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 14px; font-size: 0.88rem; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.2);">
                👤 <?php echo $primeiro_nome; ?> ▼
              </button>
              <div id="drop-perfil" class="dropdown-conteudo">
                <a href="dashboard.php" class="dropdown-item">📈 Meu Painel</a>
                <div class="dropdown-divisor"></div>
                <a href="logout.php" class="dropdown-item sair">🚪 Sair da Conta</a>
              </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-entrar" style="margin-right: 15px;">Entrar</a>
            <a href="login.php?registro=1" class="btn-comecar">Cadastre-se</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <section class="hero">
    <div class="hero-badge">🎓 Preparatório Enem</div>
    <h1>A Química do ENEM,<br><span>Descomplicada para Você.</span></h1>
    <p>O primeiro ecossistema de aprendizado de Química criado por alunos para alunos. Simulados inteligentes, análises de desempenho precisas e laboratórios virtuais.</p>
    
    <div class="hero-buttons">
      <?php if ($logado): ?>
          <a href="dashboard.php" class="btn-comecar" style="font-size: 1.1rem; padding: 15px 35px;">Continuar Estudando</a>
      <?php else: ?>
          <a href="login.php?registro=1" class="btn-comecar" style="font-size: 1.1rem; padding: 15px 35px;">Começar Gratuitamente</a>
          <a href="#plataforma" class="btn-entrar" style="font-size: 1.1rem; padding: 13px 30px; border: 2px solid var(--borda); border-radius: 10px; margin-left: 10px;">Conhecer mais</a>
      <?php endif; ?>
    </div>
  </section>

  <section id="plataforma" class="features">
    <div class="features-container">
      <div class="section-title">
        <h2>Tudo o que você precisa para a nota 1000</h2>
        <p>Desenvolvido com tecnologia de ponta para otimizar o seu tempo de estudo.</p>
      </div>

      <div class="grid-features">
        <div class="card-feature">
          <div class="feature-icon">⏱️</div>
          <h3>Simulados Modo Prova</h3>
          <p>Gere cadernos de questões personalizados. Treine com o cronômetro ativo e acostume-se com a verdadeira pressão e o tempo de resolução do ENEM.</p>
        </div>
        <div class="card-feature">
          <div class="feature-icon">📊</div>
          <h3>Radar de Proficiência</h3>
          <p>Nossa plataforma mapeia automaticamente seus acertos e erros. Descubra exatamente em quais subtópicos você é mestre e quais precisa revisar urgentemente.</p>
        </div>
        <div class="card-feature">
          <div class="feature-icon">💡</div>
          <h3>IA Pedagógica</h3>
          <p>Esqueça os PDFs gigantes. Nosso sistema analisa o seu progresso e gera rotas e dicas de estudos baseadas nas suas maiores dificuldades diárias.</p>
        </div>
      </div>
    </div>
  </section>

  <section id="interativos" class="interativos-section">
    <div class="section-title">
      <h2>Experiência Interativa</h2>
      <p>Aprenda visualmente com as nossas ferramentas nativas e conheça a história.</p>
    </div>

    <div class="simulador-box">
      <div class="simulador-info">
        <span style="color: #f472b6; font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 10px;">Laboratório Virtual</span>
        <h2>Simulador de pH</h2>
        <p>A teoria na ponta dos seus dedos. Mova o controle deslizante ao lado para simular e entender o comportamento ácido, neutro ou básico das substâncias em tempo real.</p>
        <ul style="color: #cbd5e1; line-height: 1.8; font-size: 1.05rem; list-style: none; padding: 0;">
            <li>🔴 <strong style="color: white;">0 a 6:</strong> Substâncias Ácidas</li>
            <li>🟢 <strong style="color: white;">7:</strong> Substância Neutra (Água)</li>
            <li>🔵 <strong style="color: white;">8 a 14:</strong> Substâncias Básicas</li>
        </ul>
      </div>
      
      <div class="controle-ph">
        <div class="ph-display" id="valorPh">7.0</div>
        <div class="ph-sub" id="statusPh" style="color: #10b981;">NEUTRO</div>
        <input type="range" id="ph" min="0" max="14" step="0.1" value="7">
        <div style="display: flex; justify-content: space-between; margin-top: 15px; font-size: 0.85rem; color: #64748b; font-weight: 700;">
            <span>Ácido (0)</span>
            <span>Básico (14)</span>
        </div>
      </div>
    </div>

    <div class="grid-flashcards">
        <div class="flip-card">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <h4>10²³</h4>
                    <p>Passe o mouse</p>
                </div>
                <div class="flip-card-back">
                    <h4>Número de Avogadro</h4>
                    <p>Representa a quantidade de entidades (átomos, moléculas, íons) presentes em 1 Mol de qualquer substância.</p>
                </div>
            </div>
        </div>
        
        <div class="flip-card">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <h4>Na</h4>
                    <p>Passe o mouse</p>
                </div>
                <div class="flip-card-back">
                    <h4>Sódio (Z = 11)</h4>
                    <p>Metal alcalino macio e prateado. Altamente reativo com a água, forma o sal de cozinha ligado ao Cloro.</p>
                </div>
            </div>
        </div>

        <div class="flip-card">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <h4 style="font-size: 2.5rem;">C₆H₁₂O₆</h4>
                    <p>Passe o mouse</p>
                </div>
                <div class="flip-card-back">
                    <h4>Glicose</h4>
                    <p>Um monossacarídeo vital. É a principal fonte de energia química para o metabolismo celular dos seres vivos.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="section-title" style="margin-bottom: 40px; margin-top: 40px;">
        <h2 style="font-size: 2rem;">Galeria de Notáveis</h2>
        <p>Os pilares que construíram o que sabemos sobre a química moderna. (Passe o mouse)</p>
    </div>
    <div class="notaveis-container">
        <div class="notavel-card bg-curie">
            <div class="notavel-info">
                <h3 class="notavel-nome">Marie Curie</h3>
                <p class="notavel-desc">Pioneira na pesquisa da radioatividade. Única pessoa a ganhar o Nobel em duas áreas científicas distintas.</p>
            </div>
        </div>
        <div class="notavel-card bg-lavoisier">
            <div class="notavel-info">
                <h3 class="notavel-nome">Antoine Lavoisier</h3>
                <p class="notavel-desc">Pai da Química Moderna. Formulou a Lei da Conservação das Massas: "Na natureza, nada se cria..."</p>
            </div>
        </div>
        <div class="notavel-card bg-mendeleev">
            <div class="notavel-info">
                <h3 class="notavel-nome">Dmitri Mendeleev</h3>
                <p class="notavel-desc">Criador da Tabela Periódica, prevendo com sucesso as propriedades de elementos ainda não descobertos.</p>
            </div>
        </div>
        <div class="notavel-card bg-bohr">
            <div class="notavel-info">
                <h3 class="notavel-nome">Niels Bohr</h3>
                <p class="notavel-desc">Propôs o modelo atômico com órbitas quantizadas, revolucionando a mecânica quântica.</p>
            </div>
        </div>
        <div class="notavel-card bg-pauling">
            <div class="notavel-info">
                <h3 class="notavel-nome">Linus Pauling</h3>
                <p class="notavel-desc">Desvendou as ligações químicas e criou a escala de Eletronegatividade. Duas vezes ganhador do Nobel.</p>
            </div>
        </div>
    </div>

    <div class="desafio-box">
        <span style="color: var(--laranja-enem); font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 15px;">Teste de Fogo Diário 🔥</span>
        
        <?php if (!empty($alts_desafio)): ?>
            <div class="desafio-pergunta"><?php echo nl2br(htmlspecialchars($desafio_enunciado)); ?></div>
            <div class="desafio-opcoes" id="opcoesDesafio">
                <?php foreach ($alts_desafio as $alt): ?>
                    <button class="btn-opcao" onclick="verificarRespostaDesafio(this, <?php echo $alt['eh_correta']; ?>, '<?php echo $letra_correta_desafio; ?>')">
                        <strong><?php echo $alt['letra']; ?>)</strong> <?php echo htmlspecialchars($alt['texto_alternativa']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="desafio-pergunta">Qual das misturas abaixo é classificada como um sistema Homogêneo?</div>
            <div class="desafio-opcoes" id="opcoesDesafio">
                <button class="btn-opcao" onclick="verificarRespostaDesafio(this, 0, 'B')"><strong>A)</strong> Água e Óleo</button>
                <button class="btn-opcao" onclick="verificarRespostaDesafio(this, 1, 'B')"><strong>B)</strong> Água e Sal (dissolvido)</button>
                <button class="btn-opcao" onclick="verificarRespostaDesafio(this, 0, 'B')"><strong>C)</strong> Granito</button>
                <button class="btn-opcao" onclick="verificarRespostaDesafio(this, 0, 'B')"><strong>D)</strong> Sangue humano</button>
            </div>
        <?php endif; ?>

        <div id="feedbackDesafio"></div>
    </div>
  </section>

  <section class="sobre-teaser">
    <div class="sobre-container">
      <div class="badge-ifsul">📍 IFSULDEMINAS - Campus Pouso Alegre</div>
      <h2>Feito por alunos. Para alunos.</h2>
      <p>O Atomicamente nasceu de um <strong>Projeto Integrador</strong> unindo forças de duas áreas de excelência técnica. Somos <strong>6 alunas de Química</strong> e <strong>1 aluno de Informática</strong> do ensino médio integrado, unidos por um único propósito: democratizar o ensino de Química de alta qualidade para o ENEM.</p>
      
      <a href="sobre.php" class="btn-comecar" style="display: inline-block; padding: 12px 30px; font-size: 1.05rem;">
        Conheça a Nossa Equipe e História
      </a>
    </div>
  </section>

  <footer class="footer">
    <div class="footer-content">
      <div class="footer-col" style="max-width: 300px;">
        <div class="marca" style="margin-bottom: 15px;">
            <img src="assets/icone-simplificado.png" alt="Logo" style="background: var(--roxo-base); padding: 4px;" />
            Atomicamente
        </div>
        <p style="color: var(--texto-secundario); font-size: 0.95rem; line-height: 1.6;">O ecossistema definitivo para revolucionar seus estudos de Química para o ENEM e Vestibulares.</p>
      </div>
      
      <div class="footer-col">
        <h4>Plataforma</h4>
        <a href="login.php">Entrar na Conta</a>
        <a href="login.php?registro=1">Criar Conta Grátis</a>
        <a href="#interativos">Laboratório Virtual</a>
      </div>

      <div class="footer-col">
        <h4>Institucional</h4>
        <a href="sobre.php">Nossa História (O Projeto)</a>
        <a href="sobre.php#equipe">A Equipe</a>
        <a href="contato.php">Fale Conosco</a>
      </div>
    </div>
    
    <div style="max-width: 1200px; margin: 40px auto 0 auto; padding-top: 20px; border-top: 1px solid var(--borda); text-align: center; color: var(--texto-secundario); font-size: 0.85rem; font-weight: 500;">
        &copy; <?php echo date('Y'); ?> Projeto Atomicamente. IFSULDEMINAS - Campus Pouso Alegre. Todos os direitos reservados.
    </div>
  </footer>

  <script>
    // 1. CONTROLADOR DE DROPDOWNS
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

    // 2. SISTEMA DE DARK MODE COERENTE
    function alternarModoNoturno() {
        const modoAtual = document.documentElement.getAttribute('data-theme');
        const spanTexto = document.getElementById('btn-tema-texto');
        if (modoAtual === 'dark') {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('temaAtomicamente', 'light');
            if(spanTexto) spanTexto.innerText = '🌙 Modo Escuro';
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('temaAtomicamente', 'dark');
            if(spanTexto) spanTexto.innerText = '☀️ Modo Claro';
        }
    }

    const temaSalvo = localStorage.getItem('temaAtomicamente');
    if (temaSalvo === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        const spanTexto = document.getElementById('btn-tema-texto');
        if(spanTexto) spanTexto.innerText = '☀️ Modo Claro';
    }

    // 3. SIMULADOR DE pH
    const sliderPh = document.getElementById('ph');
    const valorPh = document.getElementById('valorPh');
    const statusPh = document.getElementById('statusPh');
    function atualizarPh() {
        const ph = parseFloat(sliderPh.value).toFixed(1);
        valorPh.innerText = ph;
        if (ph < 7) {
            statusPh.innerText = "ÁCIDO";
            statusPh.style.color = "#ef4444";
        } else if (ph > 7) {
            statusPh.innerText = "BÁSICO";
            statusPh.style.color = "#3b82f6";
        } else {
            statusPh.innerText = "NEUTRO";
            statusPh.style.color = "#10b981";
        }
    }
    if(sliderPh) sliderPh.addEventListener('input', atualizarPh);
    
    // 4. VERIFICAÇÃO DO MINI DESAFIO COM FILOSOFIA DE DESIGN PREMIUM
    let desafioRespondido = false;
    function verificarRespostaDesafio(botaoClicado, isCorreta, letraGabarito) {
        if(desafioRespondido) return; 
        desafioRespondido = true;
        
        const botoes = document.querySelectorAll('.btn-opcao');
        const feedback = document.getElementById('feedbackDesafio');
        
        botoes.forEach(btn => {
            btn.style.opacity = '0.4';
            btn.style.cursor = 'default';
            if(btn.innerText.trim().startsWith(letraGabarito + ')')) {
                btn.classList.add('correta');
                btn.style.opacity = '1';
            }
        });

        if(isCorreta == 0) {
            botaoClicado.classList.add('errada');
            botaoClicado.style.opacity = '1';
            feedback.innerHTML = `
                <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 8px;">❌ Quase lá! Alternativa incorreta.</div>
                <div style="color: inherit; font-size: 1rem; font-weight: 500; margin-bottom: 5px;">A resposta correta para esta questão é a letra <strong style="font-weight: 800;">${letraGabarito}</strong>.</div>
                <div style="color: inherit; font-size: 0.95rem; opacity: 0.9;">Não desanime, o erro faz parte da jornada rumo à aprovação!</div>
                <div class="container-botoes-feedback">
                    <a href="login.php?registro=1" class="btn-cta-desafio">Ver Resolução Completa</a>
                    <a href="index.php" class="btn-cta-secundario">Tentar Outra Questão 🔄</a>
                </div>
            `;
            feedback.style.backgroundColor = "rgba(239, 68, 68, 0.08)";
            feedback.style.color = "#ef4444";
            feedback.style.border = "1px solid rgba(239, 68, 68, 0.3)";
        } else {
            feedback.innerHTML = `
                <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 8px;">🎉 Excelente! Resposta correta!</div>
                <div style="color: inherit; font-size: 1rem; font-weight: 500; margin-bottom: 5px;">Você demonstrou um domínio afiado sobre este conteúdo.</div>
                <div style="color: inherit; font-size: 0.95rem; opacity: 0.9;">Mantenha esse ritmo forte de estudos para garantir sua vaga!</div>
                <div class="container-botoes-feedback">
                    <a href="login.php?registro=1" class="btn-cta-desafio">Gerar Simulado Completo</a>
                    <a href="index.php" class="btn-cta-secundario">Tentar Outra Questão 🔄</a>
                </div>
            `;
            feedback.style.backgroundColor = "rgba(16, 185, 129, 0.08)";
            feedback.style.color = "#10b981";
            feedback.style.border = "1px solid rgba(16, 185, 129, 0.3)";
        }
        feedback.style.display = "block";
    }

    // 5. EFECT NAVBAR COMPORTAMENTO AO ROLAR
    window.addEventListener('scroll', () => {
        const header = document.querySelector('.header-site');
        const modoAtual = document.documentElement.getAttribute('data-theme');
        if (window.scrollY > 20) {
            header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.06)';
            header.style.background = modoAtual === 'dark' ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)';
        } else {
            header.style.boxShadow = 'none';
            header.style.background = modoAtual === 'dark' ? 'rgba(15, 23, 42, 0.85)' : 'rgba(255, 255, 255, 0.85)';
        }
    });
  </script>
</body>
</html>
