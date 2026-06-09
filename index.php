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
    // Busca 1 questão aleatória do banco (Apenas o sorteio inicial)
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
  
  <!-- Fontes Premium do Google -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  
  <style>
    /* ================= VARIÁVEIS DE TEMA ================= */
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
      --nav-bg: rgba(255, 255, 255, 0.7);
      --nav-borda: rgba(147, 51, 234, 0.15);
      --icon-wrapper-bg: rgba(168, 85, 247, 0.1);
    }

    [data-theme="dark"] {
      --roxo-base: #a855f7;
      --roxo-escuro: #c4b5fd;
      --roxo-claro: #2e1065;
      --texto-principal: #f8fafc;
      --texto-secundario: #94a3b8;
      --bg-global: #0f172a;
      --bg-card: #1e293b;
      --borda: #334155;
      --nav-bg: rgba(15, 23, 42, 0.7);
      --nav-borda: rgba(255, 255, 255, 0.05);
      --icon-wrapper-bg: rgba(168, 85, 247, 0.08);
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-global);
      color: var(--texto-principal);
      margin: 0; padding: 0; overflow-x: hidden;
      transition: background-color 0.5s ease, color 0.5s ease;
    }

    /* ================= CABEÇALHO PREMIUM ================= */
    .header-site {
      position: fixed; top: 0; width: 100%;
      background: var(--nav-bg); 
      backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--nav-borda);
      z-index: 1000; 
      transition: background 0.5s ease, border-color 0.5s ease, box-shadow 0.3s ease;
    }
    
    .nav-container {
      max-width: 1200px; margin: 0 auto; padding: 12px 20px;
      display: flex; justify-content: space-between; align-items: center;
    }
    
    .marca {
      display: flex; align-items: center; gap: 10px; text-decoration: none;
    }
    .marca img { height: 38px; filter: drop-shadow(0 2px 4px rgba(147, 51, 234, 0.2)); }
    .marca span { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; font-weight: 800; color: #7c3aed; letter-spacing: -0.5px; }

    .nav-links { display: flex; gap: 25px; align-items: center; }
    .nav-links a {
      text-decoration: none; color: var(--texto-secundario);
      font-weight: 600; font-size: 0.95rem; transition: color 0.2s;
    }
    .nav-links a:hover { color: var(--roxo-base); }
    
    .nav-actions { display: flex; align-items: center; gap: 16px; }

    .theme-toggle-btn {
        background: var(--icon-wrapper-bg); border: 1px solid var(--borda);
        color: #a855f7; cursor: pointer; width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.3s ease; outline: none; padding: 0;
    }
    .theme-toggle-btn:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(168, 85, 247, 0.2); }
    .theme-toggle-btn svg { width: 20px; height: 20px; fill: currentColor; transition: transform 0.5s ease; }
    body:not([data-theme="dark"]) .icon-sun, body[data-theme="dark"] .icon-moon { display: none; }
    body[data-theme="dark"] .icon-sun { display: block; }

    .btn-entrar {
        padding: 10px 20px; background: rgba(147, 51, 234, 0.1);
        color: #7c3aed; text-decoration: none; border-radius: 8px;
        font-weight: 600; font-size: 0.9rem; border: 1px solid rgba(147, 51, 234, 0.2);
        transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center;
    }
    .btn-entrar:hover { background: #7c3aed; color: #ffffff; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }
    
    .btn-comecar {
        background: linear-gradient(135deg, #7c3aed, #a855f7); color: white !important;
        padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; text-decoration: none;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3); transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-comecar:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4); }

    .menu-dropdown { position: relative; display: inline-block; }
    .dropdown-conteudo {
        display: none; position: absolute; right: 0; top: 115%;
        background-color: var(--bg-card); min-width: 180px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-radius: 12px;
        border: 1px solid var(--borda); overflow: hidden; z-index: 200;
        animation: dropAnim 0.2s ease forwards;
    }
    @keyframes dropAnim { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .dropdown-conteudo.mostrar { display: block; }
    .dropdown-item {
        color: var(--texto-principal); padding: 12px 16px; text-decoration: none;
        display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; transition: background 0.2s; cursor: pointer;
    }
    .dropdown-item:hover { background-color: var(--bg-global); color: var(--roxo-base); }
    .dropdown-divisor { height: 1px; background-color: var(--borda); margin: 4px 0; }
    .sair { color: #ef4444; } .sair:hover { color: #dc2626; background-color: rgba(239, 68, 68, 0.05); }

    /* ================= VETORES E ICONES AUXILIARES ================= */
    .icon-inline { vertical-align: middle; display: inline-block; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* ================= PALAVRAS PISCANDO (Duas intensidades) ================= */
    .blinking-word, .blinking-word-subtle {
        position: absolute;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800;
        color: var(--roxo-base);
        pointer-events: none;
        opacity: 0;
        z-index: 1;
        text-transform: uppercase; /* Mantido em Letra de Forma conforme solicitado */
        letter-spacing: 1px;
        white-space: nowrap;
    }

    /* Animação Normal (Hero - Maior Visibilidade) */
    .blinking-word { animation: hologramaAnimNormal linear forwards; }
    @keyframes hologramaAnimNormal {
        0% { opacity: 0; transform: translateY(10px); }
        15% { opacity: 0.18; transform: translateY(0); }
        85% { opacity: 0.18; transform: translateY(-25px); }
        100% { opacity: 0; transform: translateY(-40px); }
    }

    /* Animação Sutil (Sobre Nós - Levemente menos sutil: opacidade subiu para 0.12) */
    .blinking-word-subtle { animation: hologramaAnimSubtil linear forwards; }
    @keyframes hologramaAnimSubtil {
        0% { opacity: 0; transform: translateY(10px); }
        15% { opacity: 0.12; transform: translateY(0); } /* Ajuste de 0.06 para 0.12 */
        85% { opacity: 0.12; transform: translateY(-15px); } /* Ajuste de 0.06 para 0.12 */
        100% { opacity: 0; transform: translateY(-25px); }
    }

    /* ================= ÁREAS COM Z-INDEX PROTEGIDO E CANVAS ================= */
    .hero-container, .middle-container, .sobre-teaser { position: relative; overflow: hidden; }
    #hero-canvas, #middle-canvas, #sobre-canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }

    /* ================= HERO SECTION ================= */
    .hero {
      position: relative; z-index: 2; 
      padding: 180px 20px 120px 20px; max-width: 1200px; margin: 0 auto;
      text-align: center; display: flex; flex-direction: column; align-items: center;
    }
    .hero-badge {
      background: rgba(234, 88, 12, 0.1); color: var(--laranja-enem);
      padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 800;
      text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px;
      border: 1px solid rgba(234, 88, 12, 0.2);
    }
    .hero h1 {
      font-family: 'Plus Jakarta Sans', sans-serif; font-size: 4.5rem; font-weight: 800; letter-spacing: -0.04em;
      margin: 0 0 20px 0; line-height: 1.1; color: var(--texto-principal); max-width: 900px; transition: color 0.3s ease;
    }
    .hero h1 span { background: linear-gradient(135deg, var(--roxo-base), #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .hero p { font-size: 1.25rem; color: var(--texto-secundario); max-width: 700px; line-height: 1.6; margin: 0 0 40px 0; transition: color 0.3s ease; }
    .hero-buttons { display: flex; gap: 15px; justify-content: center; }

    /* ================= SEÇÕES INTERNAS DO MIDDLE CONTAINER ================= */
    .middle-container { background-color: var(--bg-global); transition: background-color 0.5s ease; }
    .middle-container section { position: relative; z-index: 2; }

    /* ================= FEATURES ================= */
    .features { padding: 100px 20px; border-top: 1px solid var(--borda); border-bottom: 1px solid var(--borda); transition: border-color 0.5s ease;}
    .features-container { max-width: 1200px; margin: 0 auto; }
    .section-title { text-align: center; margin-bottom: 60px; }
    .section-title h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.5rem; font-weight: 800; margin: 0 0 15px 0; letter-spacing: -0.03em;}
    .section-title p { color: var(--texto-secundario); font-size: 1.1rem; margin: 0; transition: color 0.5s ease; }
    
    .grid-features { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
    .card-feature { background: var(--bg-card); padding: 40px 30px; border-radius: 24px; border: 1px solid var(--borda); transition: all 0.3s ease; }
    .card-feature:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.05); border-color: var(--roxo-base); }
    
    .feature-icon-wrapper { 
        width: 56px; height: 56px; border-radius: 16px; margin-bottom: 24px;
        display: flex; align-items: center; justify-content: center;
        background: var(--icon-wrapper-bg); color: var(--roxo-base);
        border: 1px solid rgba(168, 85, 247, 0.15);
    }
    .feature-icon-wrapper svg { width: 26px; height: 26px; stroke: currentColor; stroke-width: 2; fill: none; }
    .card-feature h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.3rem; font-weight: 800; margin: 0 0 10px 0; }
    .card-feature p { color: var(--texto-secundario); line-height: 1.6; margin: 0; font-size: 0.95rem; }

    /* ================= SEÇÃO INTERATIVOS ================= */
    .interativos-section { padding: 100px 20px; max-width: 1200px; margin: 0 auto; }
    
    .simulador-box {
      background: linear-gradient(145deg, #0f172a, #1e1b4b); border-radius: 30px; padding: 60px; color: white; margin-bottom: 50px;
      box-shadow: 0 20px 50px rgba(15, 23, 42, 0.3); display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;
      border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .simulador-info h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.2rem; font-weight: 800; margin: 0 0 15px 0; }
    .simulador-info p { color: #cbd5e1; font-size: 1.05rem; line-height: 1.6; margin: 0 0 30px 0; }
    
    .ph-lista-badges { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
    .ph-lista-badges li { display: flex; align-items: center; gap: 12px; font-size: 1rem; color: #cbd5e1; }
    .dot-status { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .dot-acido { background-color: #ef4444; box-shadow: 0 0 8px #ef4444; }
    .dot-neutro { background-color: #10b981; box-shadow: 0 0 8px #10b981; }
    .dot-basico { background-color: #3b82f6; box-shadow: 0 0 8px #3b82f6; }

    .controle-ph { background: var(--bg-card); padding: 30px; border-radius: 20px; text-align: center; color: var(--texto-principal); border: 1px solid var(--borda); }
    .ph-display { font-size: 2.5rem; font-weight: 900; color: var(--roxo-base); margin-bottom: 5px; }
    .ph-sub { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; }
    input[type=range] { width: 100%; height: 8px; border-radius: 6px; outline: none; -webkit-appearance: none; background: linear-gradient(to right, #ef4444, #f59e0b, #10b981, #3b82f6, #1e3a8a); }
    input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 22px; height: 22px; border-radius: 50%; background: white; border: 4px solid var(--roxo-base); cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }

    .grid-flashcards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 80px;}
    .flip-card { background-color: transparent; height: 220px; perspective: 1000px; }
    .flip-card-inner { position: relative; width: 100%; height: 100%; text-align: center; transition: transform 0.6s; transform-style: preserve-3d; cursor: pointer; }
    .flip-card:hover .flip-card-inner { transform: rotateY(180deg); }
    .flip-card-front, .flip-card-back { position: absolute; width: 100%; height: 100%; -webkit-backface-visibility: hidden; backface-visibility: hidden; border-radius: 24px; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 25px; box-sizing: border-box; box-shadow: 0 10px 20px rgba(0,0,0,0.03); }
    .flip-card-front { background-color: var(--bg-card); color: var(--texto-principal); border: 1px solid var(--borda); transition: background-color 0.5s ease, border-color 0.5s ease; }
    .flip-card-front h4 { font-size: 3.5rem; margin: 0; color: var(--roxo-base); font-weight: 900; line-height: 1; }
    .flip-card-front p { font-size: 0.8rem; color: var(--texto-secundario); font-weight: 700; text-transform: uppercase; margin-top: 12px; letter-spacing: 0.05em; transition: color 0.5s ease;}
    .flip-card-back { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; transform: rotateY(180deg); border: 1px solid rgba(255,255,255,0.1); }
    .flip-card-back h4 { font-size: 1.4rem; margin: 0 0 10px 0; font-weight: 800; }
    .flip-card-back p { font-size: 0.95rem; line-height: 1.5; margin: 0; opacity: 0.9; font-weight: 500;}

    .notaveis-container { display: flex; height: 400px; gap: 14px; margin-bottom: 80px;}
    .notavel-card {
        flex: 1; border-radius: 24px; overflow: hidden;
        transition: flex 0.6s cubic-bezier(0.25, 0.8, 0.25, 1), border-color 0.3s;
        cursor: pointer; position: relative; border: 1px solid var(--borda);
        display: flex; flex-direction: column; justify-content: flex-end; padding: 25px;
        background-size: cover; background-position: center center; background-repeat: no-repeat;
        background-color: #1e1b4b;
    }
    .notavel-card::before { 
        content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
        background: linear-gradient(to top, rgba(15,23,42,0.95) 0%, rgba(15,23,42,0.5) 50%, rgba(15,23,42,0.2) 100%); 
        z-index: 1; transition: opacity 0.3s;
    }
    .notavel-card:hover { flex: 4; border-color: var(--roxo-base); }
    .notavel-info { position: relative; z-index: 2; color: white; text-align: left; }
    .notavel-nome { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.4rem; margin: 0; white-space: nowrap; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
    .notavel-desc { font-size: 0.95rem; opacity: 0; max-height: 0; transition: all 0.5s ease; margin-top: 10px; line-height: 1.6; color: #e2e8f0; }
    .notavel-card:hover .notavel-desc { opacity: 1; max-height: 150px; }

    .bg-curie     { background-image: url('assets/curie.jpg'); }
    .bg-lavoisier { background-image: url('assets/lavoisier.jpg'); }
    .bg-mendeleev { background-image: url('assets/mendeleev.jpg'); }
    .bg-bohr      { background-image: url('assets/bohr.jpg'); }
    .bg-pauling   { background-image: url('assets/pauling.jpg'); }

    /* ================= DESAFIO DE FOGO ================= */
    .desafio-box { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 30px; padding: 50px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.02); transition: background-color 0.5s ease, border-color 0.5s ease;}
    .header-desafio-title { display: inline-flex; align-items: center; gap: 8px; color: var(--laranja-enem); font-weight: 800; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 15px; }
    .header-desafio-title svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2.5; }
    
    .desafio-pergunta { font-size: 1.25rem; font-weight: 600; line-height: 1.6; margin-bottom: 30px; color: var(--texto-principal); max-width: 800px; margin-inline: auto; text-align: justify; transition: color 0.5s ease;}
    .desafio-opcoes { display: grid; grid-template-columns: 1fr; gap: 12px; max-width: 800px; margin: 0 auto; }
    .btn-opcao {
        background: var(--bg-global); border: 2px solid var(--borda); color: var(--texto-principal); text-align: left;
        padding: 18px 25px; border-radius: 16px; font-weight: 500; font-size: 1.05rem; cursor: pointer; transition: all 0.2s; line-height: 1.4;
    }
    .btn-opcao:hover { border-color: var(--roxo-base); background: var(--icon-wrapper-bg); transform: translateX(5px); }
    .btn-opcao.correta { background: #10b981 !important; color: white !important; border-color: #059669 !important; }
    .btn-opcao.errada { background: #ef4444 !important; color: white !important; border-color: #dc2626 !important; }
    
    #feedbackDesafio { 
        margin-top: 30px; display: none; padding: 30px; border-radius: 20px; 
        max-width: 800px; margin-inline: auto; text-align: left; line-height: 1.6;
        animation: dropAnim 0.3s ease-out forwards;
    }
    .container-botoes-feedback { display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap; justify-content: flex-start; align-items: center; }
    .btn-cta-desafio { 
        display: inline-flex; align-items: center; justify-content: center; padding: 14px 28px; background: var(--roxo-base); color: white !important; 
        text-decoration: none; border-radius: 14px; font-weight: 700; font-size: 1rem; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25); transition: all 0.2s ease;
    }
    .btn-cta-desafio:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(124, 58, 237, 0.4); }

    /* ================= SOBRE NÓS (TEASER) ================= */
    .sobre-teaser { 
        background: var(--icon-wrapper-bg); padding: 100px 20px; text-align: center; transition: background-color 0.5s ease;
    }
    .sobre-container { position: relative; z-index: 2; max-width: 800px; margin: 0 auto; }
    .badge-ifsul { background: var(--bg-card); color: var(--roxo-base); padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; display: inline-block; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid var(--borda); transition: background-color 0.5s ease, border-color 0.5s ease;}
    .sobre-container h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2.5rem; font-weight: 800; margin: 0 0 20px 0; color: var(--texto-principal); letter-spacing: -0.02em; transition: color 0.5s ease;}
    .sobre-container p { font-size: 1.15rem; color: var(--texto-secundario); line-height: 1.6; margin: 0 0 35px 0; transition: color 0.5s ease; }

    /* ================= FOOTER ================= */
    .footer { background: var(--bg-card); border-top: 1px solid var(--borda); padding: 60px 20px; transition: background-color 0.5s ease, border-color 0.5s ease;}
    .footer-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 40px; }
    .footer-col h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; margin: 0 0 20px 0; color: var(--texto-principal); }
    .footer-col a { display: block; color: var(--texto-secundario); text-decoration: none; margin-bottom: 12px; transition: color 0.2s; font-weight: 500;}
    .footer-col a:hover { color: var(--roxo-base); }
    
    @media (max-width: 900px) {
        .nav-links { display: none; } 
        .hero h1 { font-size: 3rem; }
        .hero-buttons { flex-direction: column; }
        .hero-buttons a { margin-left: 0 !important; }
        .simulador-box { grid-template-columns: 1fr; padding: 40px 20px; }
        .notaveis-container { flex-direction: column; height: auto; }
        .notavel-card { padding: 40px 20px; height: 120px; }
        .notavel-card:hover { height: 250px; }
        .notavel-card .notavel-desc { opacity: 1; max-height: none; margin-top: 5px;}
        .container-botoes-feedback { flex-direction: column; width: 100%; }
        .btn-cta-desafio { width: 100%; text-align: center; }
    }
  </style>
</head>
<body>

  <header class="header-site">
    <div class="nav-container">
      <a href="index.php" class="marca">
        <img src="assets/icone-simplificado.png" alt="Logo" />
        <span>Atomicamente</span>
      </a>
      
      <nav class="nav-links">
        <a href="#plataforma">A Plataforma</a>
        <a href="#interativos">Interativos</a>
        <a href="sobre.php">Sobre Nós</a>
        <a href="contato.php">Contato</a>
      </nav>

      <div class="nav-actions">
        <button id="theme-btn" class="theme-toggle-btn" aria-label="Alternar Tema" onclick="alternarModoNoturno()">
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
        </button>

        <?php if ($logado): ?>
            <div class="menu-dropdown">
              <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 10px 16px; font-size: 0.9rem; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.2);">
                <svg class="icon-inline" viewBox="0 0 24 24" width="16" height="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <?php echo htmlspecialchars($primeiro_nome); ?> ▼
              </button>
              <div id="drop-perfil" class="dropdown-conteudo">
                <a href="dashboard.php" class="dropdown-item">
                    <svg class="icon-inline" viewBox="0 0 24 24" width="16" height="16"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                    Meu Painel
                </a>
                <div class="dropdown-divisor"></div>
                <a href="logout.php" class="dropdown-item sair">
                    <svg class="icon-inline" viewBox="0 0 24 24" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Sair da Conta
                </a>
              </div>
            </div>
        <?php else: ?>
            <a href="login.php" class="btn-entrar" style="margin-right: 5px;">Entrar</a>
            <a href="login.php?registro=1" class="btn-comecar">Cadastre-se</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- CONTAINER DO HERO -->
  <div class="hero-container" id="hero-area">
      <canvas id="hero-canvas"></canvas>
      
      <section class="hero">
        <div class="hero-badge">🎓 Preparatório Enem</div>
        <h1>A Química do ENEM,<br><span>Descomplicada para Você.</span></h1>
        <p>O primeiro ecossistema de aprendizado de Química criado por alunos para alunos. Simulados inteligentes, análises de desempenho precisas e laboratórios virtuais.</p>
        
        <div class="hero-buttons">
          <?php if ($logado): ?>
              <a href="dashboard.php" class="btn-comecar" style="font-size: 1.05rem; padding: 14px 32px;">Continuar Estudando</a>
          <?php else: ?>
              <a href="login.php?registro=1" class="btn-comecar" style="font-size: 1.05rem; padding: 14px 32px;">Começar Gratuitamente</a>
              <a href="#plataforma" class="btn-entrar" style="font-size: 1.05rem; padding: 14px 30px; background: var(--bg-card); color: var(--texto-principal); border-color: var(--borda);">Conhecer mais</a>
          <?php endif; ?>
        </div>
      </section>
  </div>

  <!-- CONTAINER GLOBAL PARA SEÇÕES DO MEIO -->
  <div class="middle-container" id="middle-area">
      <canvas id="middle-canvas"></canvas>

      <section id="plataforma" class="features">
        <div class="features-container">
          <div class="section-title">
            <h2>Tudo o que você precisa para a nota 1000</h2>
            <p>Desenvolvido com tecnologia de ponta para otimizar o seu tempo de estudo.</p>
          </div>

          <div class="grid-features">
            <div class="card-feature">
              <div class="feature-icon-wrapper">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              </div>
              <h3>Simulados Modo Prova</h3>
              <p>Gere cadernos de questões personalizados. Treine com o cronômetro ativo e acostume-se com a verdadeira pressão e o tempo de resolução do ENEM.</p>
            </div>
            <div class="card-feature">
              <div class="feature-icon-wrapper">
                <svg viewBox="0 0 24 24"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
              </div>
              <h3>Radar de Proficiência</h3>
              <p>Nossa plataforma mapeia automaticamente seus acertos e erros. Descubra exatamente em quais subtópicos você é mestre e quais precisa revisar urgentemente.</p>
            </div>
            <div class="card-feature">
              <div class="feature-icon-wrapper">
                <svg viewBox="0 0 24 24"><path d="M9 21h6"></path><path d="M9 17h6"></path><path d="M10 13a5 5 0 1 1 4 0v4H10v-4z"></path></svg>
              </div>
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
            
            <ul class="ph-lista-badges">
                <li><span class="dot-status dot-acido"></span> <strong>0 a 6:</strong> Substâncias Ácidas</li>
                <li><span class="dot-status dot-neutro"></span> <strong>7:</strong> Substância Neutra (Água)</li>
                <li><span class="dot-status dot-basico"></span> <strong>8 a 14:</strong> Substâncias Básicas</li>
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
            <div class="header-desafio-title">
                <svg viewBox="0 0 24 24"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 3.5z"></path></svg>
                Teste de Fogo Diário
            </div>
            
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
  </div>

  <!-- CONTAINER SOBRE NÓS -->
  <section class="sobre-teaser" id="sobre-area">
    <canvas id="sobre-canvas"></canvas>
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
            <img src="assets/icone-simplificado.png" alt="Logo" style="background: var(--roxo-base); padding: 4px; border-radius: 8px;" />
            <span style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.25rem; font-weight: 800; color: var(--texto-principal);">Atomicamente</span>
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

    // 2. SISTEMA DE DARK MODE E SINCRONIA DO HEADER
    function atualizarCoresHeader() {
        const header = document.querySelector('.header-site');
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        
        if (window.scrollY > 20) {
            header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.06)';
            header.style.background = isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)';
        } else {
            header.style.boxShadow = 'none';
            header.style.background = isDark ? 'rgba(15, 23, 42, 0.7)' : 'rgba(255, 255, 255, 0.7)';
        }
    }

    function alternarModoNoturno() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        if (isDark) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('temaAtomicamente', 'light');
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('temaAtomicamente', 'dark');
        }
        atualizarCoresHeader();
    }

    if (localStorage.getItem('temaAtomicamente') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
    
    window.addEventListener('scroll', atualizarCoresHeader);
    atualizarCoresHeader();

    // ================= 3. ANIMAÇÃO DE REDE: HERO (Átomos Fluidos) =================
    const canvasHero = document.getElementById('hero-canvas');
    const ctxHero = canvasHero.getContext('2d');
    let particlesHero = [];
    const mouseHero = { x: null, y: null, radius: 130 };

    function resizeCanvasHero() {
        canvasHero.width = canvasHero.parentElement.offsetWidth;
        canvasHero.height = canvasHero.parentElement.offsetHeight;
    }
    
    class ParticleHero {
        constructor() {
            this.x = Math.random() * canvasHero.width;
            this.y = Math.random() * canvasHero.height;
            this.size = Math.random() * 2 + 1;
            this.speedX = (Math.random() - 0.5) * 0.4;
            this.speedY = (Math.random() - 0.5) * 0.4;
        }
        update() {
            this.x += this.speedX; this.y += this.speedY;
            if (this.x > canvasHero.width || this.x < 0) this.speedX = -this.speedX;
            if (this.y > canvasHero.height || this.y < 0) this.speedY = -this.speedY;

            if (mouseHero.x != null && mouseHero.y != null) {
                let dx = this.x - mouseHero.x; let dy = this.y - mouseHero.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouseHero.radius) {
                    let force = (mouseHero.radius - distance) / mouseHero.radius;
                    this.x += (dx / distance) * force * 1.2;
                    this.y += (dy / distance) * force * 1.2;
                }
            }
        }
        draw(isDark) {
            ctxHero.fillStyle = isDark ? 'rgba(168, 85, 247, 0.25)' : 'rgba(124, 58, 237, 0.15)';
            ctxHero.beginPath(); ctxHero.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctxHero.fill();
        }
    }

    function initHeroParticles() {
        particlesHero = [];
        const num = Math.floor((canvasHero.width * canvasHero.height) / 13000);
        for (let i = 0; i < num; i++) particlesHero.push(new ParticleHero());
    }

    function animateHero() {
        ctxHero.clearRect(0, 0, canvasHero.width, canvasHero.height);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        particlesHero.forEach(p => { p.update(); p.draw(isDark); });
        
        for (let a = 0; a < particlesHero.length; a++) {
            for (let b = a; b < particlesHero.length; b++) {
                let dx = particlesHero[a].x - particlesHero[b].x;
                let dy = particlesHero[a].y - particlesHero[b].y;
                let dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 100) {
                    let op = (1 - (dist / 100)) * (isDark ? 0.08 : 0.06);
                    ctxHero.strokeStyle = `rgba(124, 58, 237, ${op})`;
                    ctxHero.lineWidth = 0.7; ctxHero.beginPath();
                    ctxHero.moveTo(particlesHero[a].x, particlesHero[a].y);
                    ctxHero.lineTo(particlesHero[b].x, particlesHero[b].y); ctxHero.stroke();
                }
            }
        }
        requestAnimationFrame(animateHero);
    }

    const hContainer = document.getElementById('hero-area');
    hContainer.addEventListener('mousemove', (e) => {
        const r = canvasHero.getBoundingClientRect();
        mouseHero.x = e.clientX - r.left; mouseHero.y = e.clientY - r.top;
    });
    hContainer.addEventListener('mouseleave', () => { mouseHero.x = null; mouseHero.y = null; });

    // ================= 4. ANIMAÇÃO DE REDE: SEÇÕES DO MEIO (Grafeno Sutil) =================
    const canvasMiddle = document.getElementById('middle-canvas');
    const ctxMiddle = canvasMiddle.getContext('2d');
    let particlesMiddle = [];
    const mouseMiddle = { x: null, y: null, radius: 160 };

    function resizeCanvasMiddle() {
        canvasMiddle.width = canvasMiddle.parentElement.offsetWidth;
        canvasMiddle.height = canvasMiddle.parentElement.offsetHeight;
    }

    class ParticleMiddle {
        constructor() {
            this.x = Math.random() * canvasMiddle.width;
            this.y = Math.random() * canvasMiddle.height;
            this.size = Math.random() * 1.5 + 0.5;
            this.speedX = (Math.random() - 0.5) * 0.2;
            this.speedY = (Math.random() - 0.5) * 0.2;
        }
        update() {
            this.x += this.speedX; this.y += this.speedY;
            if (this.x > canvasMiddle.width || this.x < 0) this.speedX = -this.speedX;
            if (this.y > canvasMiddle.height || this.y < 0) this.speedY = -this.speedY;

            if (mouseMiddle.x != null && mouseMiddle.y != null) {
                let dx = this.x - mouseMiddle.x; let dy = this.y - mouseMiddle.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouseMiddle.radius) {
                    let force = (mouseMiddle.radius - distance) / mouseMiddle.radius;
                    this.x -= (dx / distance) * force * 0.5;
                    this.y -= (dy / distance) * force * 0.5;
                }
            }
        }
        draw(isDark) {
            ctxMiddle.fillStyle = isDark ? 'rgba(147, 51, 234, 0.15)' : 'rgba(147, 51, 234, 0.08)';
            ctxMiddle.beginPath(); ctxMiddle.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctxMiddle.fill();
        }
    }

    function initMiddleParticles() {
        particlesMiddle = [];
        const num = Math.floor((canvasMiddle.width * canvasMiddle.height) / 18000);
        for (let i = 0; i < num; i++) particlesMiddle.push(new ParticleMiddle());
    }

    function animateMiddle() {
        ctxMiddle.clearRect(0, 0, canvasMiddle.width, canvasMiddle.height);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        particlesMiddle.forEach(p => { p.update(); p.draw(isDark); });
        
        for (let a = 0; a < particlesMiddle.length; a++) {
            for (let b = a; b < particlesMiddle.length; b++) {
                let dx = particlesMiddle[a].x - particlesMiddle[b].x;
                let dy = particlesMiddle[a].y - particlesMiddle[b].y;
                let dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 130) {
                    let op = (1 - (dist / 130)) * (isDark ? 0.05 : 0.03);
                    ctxMiddle.strokeStyle = isDark ? `rgba(168, 85, 247, ${op})` : `rgba(124, 58, 237, ${op})`;
                    ctxMiddle.lineWidth = 0.5; ctxMiddle.beginPath();
                    ctxMiddle.moveTo(particlesMiddle[a].x, particlesMiddle[a].y);
                    ctxMiddle.lineTo(particlesMiddle[b].x, particlesMiddle[b].y); ctxMiddle.stroke();
                }
            }
        }
        requestAnimationFrame(animateMiddle);
    }

    const mContainer = document.getElementById('middle-area');
    mContainer.addEventListener('mousemove', (e) => {
        const r = canvasMiddle.getBoundingClientRect();
        mouseMiddle.x = e.clientX - r.left; mouseMiddle.y = e.clientY - r.top;
    });
    mContainer.addEventListener('mouseleave', () => { mouseMiddle.x = null; mouseMiddle.y = null; });

    // ================= 5. ANIMAÇÃO DE REDE: SOBRE NÓS (Réplica do Hero) =================
    const canvasSobre = document.getElementById('sobre-canvas');
    const ctxSobre = canvasSobre.getContext('2d');
    let particlesSobre = [];
    const mouseSobre = { x: null, y: null, radius: 130 };

    function resizeCanvasSobre() {
        canvasSobre.width = canvasSobre.parentElement.offsetWidth;
        canvasSobre.height = canvasSobre.parentElement.offsetHeight;
    }
    
    class ParticleSobre {
        constructor() {
            this.x = Math.random() * canvasSobre.width;
            this.y = Math.random() * canvasSobre.height;
            this.size = Math.random() * 2 + 1;
            this.speedX = (Math.random() - 0.5) * 0.4;
            this.speedY = (Math.random() - 0.5) * 0.4;
        }
        update() {
            this.x += this.speedX; this.y += this.speedY;
            if (this.x > canvasSobre.width || this.x < 0) this.speedX = -this.speedX;
            if (this.y > canvasSobre.height || this.y < 0) this.speedY = -this.speedY;

            if (mouseSobre.x != null && mouseSobre.y != null) {
                let dx = this.x - mouseSobre.x; let dy = this.y - mouseSobre.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouseSobre.radius) {
                    let force = (mouseSobre.radius - distance) / mouseSobre.radius;
                    this.x += (dx / distance) * force * 1.2;
                    this.y += (dy / distance) * force * 1.2;
                }
            }
        }
        draw(isDark) {
            ctxSobre.fillStyle = isDark ? 'rgba(168, 85, 247, 0.25)' : 'rgba(124, 58, 237, 0.15)';
            ctxSobre.beginPath(); ctxSobre.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctxSobre.fill();
        }
    }

    function initSobreParticles() {
        particlesSobre = [];
        const num = Math.floor((canvasSobre.width * canvasSobre.height) / 13000);
        for (let i = 0; i < num; i++) particlesSobre.push(new ParticleSobre());
    }

    function animateSobre() {
        ctxSobre.clearRect(0, 0, canvasSobre.width, canvasSobre.height);
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        particlesSobre.forEach(p => { p.update(); p.draw(isDark); });
        
        for (let a = 0; a < particlesSobre.length; a++) {
            for (let b = a; b < particlesSobre.length; b++) {
                let dx = particlesSobre[a].x - particlesSobre[b].x;
                let dy = particlesSobre[a].y - particlesSobre[b].y;
                let dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 100) {
                    let op = (1 - (dist / 100)) * (isDark ? 0.08 : 0.06);
                    ctxSobre.strokeStyle = `rgba(124, 58, 237, ${op})`;
                    ctxSobre.lineWidth = 0.7; ctxSobre.beginPath();
                    ctxSobre.moveTo(particlesSobre[a].x, particlesSobre[a].y);
                    ctxSobre.lineTo(particlesSobre[b].x, particlesSobre[b].y); ctxSobre.stroke();
                }
            }
        }
        requestAnimationFrame(animateSobre);
    }

    const sContainer = document.getElementById('sobre-area');
    sContainer.addEventListener('mousemove', (e) => {
        const r = canvasSobre.getBoundingClientRect();
        mouseSobre.x = e.clientX - r.left; mouseSobre.y = e.clientY - r.top;
    });
    sContainer.addEventListener('mouseleave', () => { mouseSobre.x = null; mouseSobre.y = null; });

    // Inicialização unificada de todos os Canvas
    window.addEventListener('resize', () => { 
        resizeCanvasHero(); resizeCanvasMiddle(); resizeCanvasSobre(); 
    });
    window.addEventListener('load', () => {
        resizeCanvasHero(); initHeroParticles(); animateHero();
        resizeCanvasMiddle(); initMiddleParticles(); animateMiddle();
        resizeCanvasSobre(); initSobreParticles(); animateSobre();
    });

    // ================= GERADOR DE PALAVRAS FILTRADO COM 2 TAMANHOS =================
    const palavrasQuimica = [
        "estequiometria", "isomeria", "termoquímica", "ph", "cinética", 
        "massa molar", "soluções", "equilíbrio", "gases", "oxirredução"
    ];

    function gerarPalavrasFlutuantes(seletor, maxPalavras, minRem, maxRem, isSubtle) {
        const container = document.querySelector(seletor);
        if (!container) return;

        setInterval(() => {
            // Conta as palavras daquele container especifico
            const currentWords = container.querySelectorAll(isSubtle ? '.blinking-word-subtle' : '.blinking-word');
            if (currentWords.length >= maxPalavras) return;
            
            const palavra = document.createElement('div');
            palavra.className = isSubtle ? 'blinking-word-subtle' : 'blinking-word';
            palavra.innerText = palavrasQuimica[Math.floor(Math.random() * palavrasQuimica.length)];
            
            const x = Math.random() * 80 + 10; 
            const y = Math.random() * 75 + 15; 
            palavra.style.left = `${x}%`;
            palavra.style.top = `${y}%`;
            
            const tamanho = Math.random() * (maxRem - minRem) + minRem;
            palavra.style.fontSize = `${tamanho}rem`;
            
            const duracao = Math.random() * 3 + 4; // 4s a 7s
            palavra.style.animationDuration = `${duracao}s`;
            
            container.appendChild(palavra);
            setTimeout(() => { palavra.remove(); }, duracao * 1000);
        }, 500);
    }

    // Hero: Animação normal, opacidade original, palavras MAIORES (1.2 a 2.8rem)
    gerarPalavrasFlutuantes('#hero-area', 8, 1.2, 2.8, false);
    
    // Sobre Nós: Animação super sutil (opacidade subiu de 0.06 para 0.12), palavras MENORES (0.6 a 1.0rem)
    gerarPalavrasFlutuantes('#sobre-area', 10, 0.6, 1.0, true);

    // ================= SIMULADOR DE pH =================
    const sliderPh = document.getElementById('ph');
    const valorPh = document.getElementById('valorPh');
    const statusPh = document.getElementById('statusPh');
    function atualizarPh() {
        const ph = parseFloat(sliderPh.value).toFixed(1);
        valorPh.innerText = ph;
        if (ph < 7) {
            statusPh.innerText = "ÁCIDO"; statusPh.style.color = "#ef4444";
        } else if (ph > 7) {
            statusPh.innerText = "BÁSICO"; statusPh.style.color = "#3b82f6";
        } else {
            statusPh.innerText = "NEUTRO"; statusPh.style.color = "#10b981";
        }
    }
    if(sliderPh) sliderPh.addEventListener('input', atualizarPh);
    
    // ================= VERIFICAÇÃO DO MINI DESAFIO =================
    let desafioRespondido = false;
    function verificarRespostaDesafio(botaoClicado, isCorreta, letraGabarito) {
        if(desafioRespondido) return; 
        desafioRespondido = true;
        
        const botoes = document.querySelectorAll('.btn-opcao');
        const feedback = document.getElementById('feedbackDesafio');
        
        botoes.forEach(btn => {
            btn.style.opacity = '0.3';
            btn.style.cursor = 'default';
            if(btn.innerText.trim().startsWith(letraGabarito + ')')) {
                btn.classList.add('correta'); btn.style.opacity = '1';
            }
        });

        if(isCorreta == 0) {
            botaoClicado.classList.add('errada'); botaoClicado.style.opacity = '1';
            feedback.innerHTML = `
                <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 8px;">Alternativa incorreta.</div>
                <div style="font-size: 1rem; font-weight: 500; margin-bottom: 5px;">A resposta correta para esta questão é a letra <strong style="font-weight: 800;">${letraGabarito}</strong>.</div>
                <div style="font-size: 0.95rem; opacity: 0.85;">Não se preocupe, a correção inteligente na plataforma guiará seus estudos.</div>
                <div class="container-botoes-feedback">
                    <a href="login.php?registro=1" class="btn-cta-desafio">Ver Resolução Completa</a>
                </div>
            `;
            feedback.style.backgroundColor = "rgba(239, 68, 68, 0.06)"; feedback.style.color = "#ef4444";
            feedback.style.border = "1px solid rgba(239, 68, 68, 0.2)";
        } else {
            feedback.innerHTML = `
                <div style="font-size: 1.15rem; font-weight: 800; margin-bottom: 8px;">Parabéns! Resposta correta!</div>
                <div style="font-size: 1rem; font-weight: 500; margin-bottom: 5px;">Você tem uma ótima base teórica sobre este assunto.</div>
                <div class="container-botoes-feedback">
                    <a href="login.php?registro=1" class="btn-cta-desafio">Gerar Simulado Completo</a>
                </div>
            `;
            feedback.style.backgroundColor = "rgba(16, 185, 129, 0.06)"; feedback.style.color = "#10b981";
            feedback.style.border = "1px solid rgba(16, 185, 129, 0.2)";
        }
        feedback.style.display = "block";
    }
  </script>
</body>
</html>
