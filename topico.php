<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sala de Estudo | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="css/plataforma.css">
</head>
<body class="dash-body">
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="index.php" class="marca-dash">⚛️ Atomicamente</a>
      <div class="user-info">
        <a href="materias.php" style="color: var(--roxo-base); font-weight: bold; text-decoration: none;">← Módulos</a>
      </div>
    </div>
  </header>

  <div class="container workspace-layout" style="display: grid; grid-template-columns: 280px 1fr; gap: 25px; padding: 30px 0;">
    
    <aside style="background: #fff; border: 1px solid var(--borda); border-radius: 16px; padding: 20px; height: fit-content;">
      <h3 style="font-size: 1rem; color: var(--roxo-base); margin-bottom: 15px;">Química Geral</h3>
      <div style="display: flex; flex-direction: column; gap: 10px;">
        <a href="topico.php?id=modelos-atomicos" style="display: flex; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 10px; border-radius: 8px; background: #f3e8ff; border-left: 4px solid var(--roxo-vivo);">
          <div style="color: #16a34a; font-weight: bold;">✓</div>
          <div>
            <span style="font-weight: bold; display: block; font-size: 0.9rem;">Modelos Atómicos</span>
            <small style="color: var(--cinza-texto); font-size: 0.75rem;">60% Concluído</small>
          </div>
        </a>
        <a href="#" style="display: flex; gap: 10px; align-items: center; text-decoration: none; color: inherit; padding: 10px; opacity: 0.6;">
          <div style="color: #ccc;">○</div>
          <div>
            <span style="font-weight: 500; display: block; font-size: 0.9rem;">Estequiometria</span>
            <small style="color: var(--cinza-texto); font-size: 0.75rem;">0% Concluído</small>
          </div>
        </a>
      </div>
    </aside>

    <main>
      <div style="display: flex; gap: 8px; border-bottom: 2px solid var(--borda); margin-bottom: 25px;">
        <button id="tabTexto" class="btn-aba-sala active" style="background: none; border: none; padding: 12px 20px; font-weight: bold; color: var(--roxo-base); cursor: pointer; border-bottom: 3px solid var(--roxo-vivo);">📝 Texto Base</button>
        <button id="tabVideo" class="btn-aba-sala" style="background: none; border: none; padding: 12px 20px; font-weight: bold; color: var(--cinza-texto); cursor: pointer; border-bottom: 3px solid transparent;">🎥 Videoaula & Fontes</button>
        <button id="tabExercicios" class="btn-aba-sala" style="background: none; border: none; padding: 12px 20px; font-weight: bold; color: var(--cinza-texto); cursor: pointer; border-bottom: 3px solid transparent;">📝 Exercícios</button>
      </div>

      <div id="painelTexto">
        <div style="background: white; padding: 30px; border-radius: 16px; border: 1px solid var(--borda);">
          <h2>Modelos Atómicos e Distribuição</h2>
          <p style="margin-top: 15px;">O desenvolvimento dos modelos atómicos foi crucial para o entendimento da estrutura da matéria. <strong>John Dalton</strong> visualizava o átomo como uma esfera maciça e indivisível (bola de bilhar).</p>
        </div>
      </div>

      <div id="painelVideo" style="display: none;">
        <div style="background: white; padding: 30px; border-radius: 16px; border: 1px solid var(--borda);">
          <h3>Videoaula de Apoio</h3>
          <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 20px 0; border-radius: 12px; background: #000;">
            <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>
          </div>
          <h4>Fontes Científicas:</h4>
          <p style="color: var(--cinza-texto); margin-top: 5px;">• Livro de Química Geral, Linus Pauling.</p>
        </div>
      </div>

      <div id="painelExercicios" style="display: none;">
        <div style="background: #eff6ff; color: #1e40af; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; margin-bottom: 20px;">💾 Respostas em tempo real. Cada clique atualiza os teus gráficos no painel.</div>
        
        <div style="background: white; padding: 25px; border-radius: 16px; border: 1px solid var(--borda);">
          <p><strong>Questão 1 (ENEM)</strong>: O modelo atómico que introduziu a natureza elétrica da matéria e a existência de eletrões foi proposto
