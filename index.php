<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Atomicamente | Ensino de Química</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Source+Serif+4:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/site.css" />
</head>
<body>
  <div class="pagina">
    <header class="topo">
      <div class="container nav">
        <a href="#inicio" class="marca">
          <div class="marca__icone">⚛️</div>
          <span>Atomicamente</span>
        </a>
        <nav class="menu" id="menuPrincipal">
          <a href="#sobre">Iniciativa</a>
          <a href="#laboratorio">Simulador de pH</a>
          <a href="dashboard.php" class="btn-destaque-enem" style="background: #7c3aed; color: white; padding: 10px 20px; border-radius: 12px; font-weight: bold; text-decoration: none; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);">Plataforma ENEM 🚀</a>
        </nav>
      </div>
    </header>

    <main>
      <section id="inicio" class="hero container" style="padding: 80px 0;">
        <div class="hero__grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center;">
          <div class="hero__conteudo">
            <span class="eyebrow" style="color: #7c3aed; text-transform: uppercase; font-weight: bold; font-size: 0.85rem; letter-spacing: 1px;">Projeto de Extensão Académica</span>
            <h1 style="font-family: 'Source Serif 4', serif; font-size: 3rem; color: #4b1d73; line-height: 1.2; margin: 15px 0;">Uma abordagem visual e organizada para a Química escolar.</h1>
            <p style="color: #6b6475; font-size: 1.1rem; margin-bottom: 25px;">Aproximando conceitos do Ensino Médio através de uma identidade clara, simuladores práticos e trilhas personalizadas baseadas no Khan Academy.</p>
            <a href="dashboard.php" class="botao" style="background: #4b1d73; color: white; padding: 15px 30px; border-radius: 99px; text-decoration: none; font-weight: bold; display: inline-block; box-shadow: 0 8px 20px rgba(75, 29, 115, 0.2);">Entrar na Área de Estudos</a>
          </div>
          <div class="hero__imagem" style="font-size: 8rem; text-align: center; animation: spin 20s linear infinite;">⚛️</div>
        </div>
      </section>

      <section id="laboratorio" class="laboratorio container" style="padding: 60px 0;">
        <div class="painel-interativo" style="background: #f7f4fb; border-radius: 24px; padding: 40px; border: 1px solid rgba(75, 29, 115, 0.1);">
          <span class="eyebrow" style="color: #7c3aed; font-weight: bold; text-transform: uppercase; font-size: 0.85rem;">Módulo Prático</span>
          <h2 style="font-size: 2rem; color: #4b1d73; margin: 10px 0;">Simulador de pH Escolar</h2>
          <p style="color: #6b6475;">Mova o controlo deslizante abaixo para simular e entender o comportamento ácido ou básico das substâncias em tempo real.</p>
          
          <div class="controle-ph" style="margin-top: 30px;">
            <div class="ph-display" style="font-size: 1.8rem; font-weight: 800; color: #4b1d73; margin-bottom: 15px;">Valor de pH selecionado: <span id="valorPh">7.0</span></div>
            <input type="range" id="ph" min="0" max="14" step="0.1" value="7" style="width: 100%; height: 12px; border-radius: 6px; background: linear-gradient(to right, #ef4444, #f59e0b, #10b981, #3b82f6, #1e3a8a); outline: none; -webkit-appearance: none; cursor: pointer;" />
            <div class="resultado-ph" id="resultadoPh" style="margin-top: 20px; padding: 20px; border-radius: 12px; background: #fff; font-weight: bold; text-align: center; border: 1px solid #eee; color: #10b981;">pH 7.0 — Solução Neutra (Água Pura).</div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <style>
    @keyframes spin { 100% { transform: rotate(360deg); } }
  </style>
  <script src="js/site.js"></script>
</body>
</html>