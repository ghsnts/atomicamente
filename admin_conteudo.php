<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Elaborar Aula | Admin</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="css/plataforma.css">
</head>
<body class="dash-body">
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="admin_dashboard.php" class="marca-dash">⚛️ Voltar ao Painel Admin</a>
    </div>
  </header>

  <div class="container" style="padding: 40px 0; max-width: 700px;">
    <div style="background: white; border: 1px solid var(--borda); padding: 30px; border-radius: 16px;">
      <h2>✍️ Publicar Matéria em Texto, Vídeo e Fontes</h2>
      
      <form action="#" method="POST" style="display: flex; flex-direction: column; gap: 20px; margin-top: 20px;" onsubmit="alert('Matéria guardada com sucesso no MySQL!'); return false;">
        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Selecionar o Tópico do ENEM:</label>
          <select style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);">
            <option value="modelos-atomicos">Química Geral -> Modelos Atómicos</option>
            <option value="estequiometria">Química Geral -> Estequiometria</option>
          </select>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Texto da Aula (Suporta HTML):</label>
          <textarea rows="8" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);" placeholder="Insira o texto completo da aula aqui..." required></textarea>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">URL do Vídeo do YouTube (Embed):</label>
          <input type="url" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);" placeholder="https://www.youtube.com/embed/..." required>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Fontes Académicas e Referências:</label>
          <textarea rows="3" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);" placeholder="Ex: Livro Primal de Química, Edição 2024..." required></textarea>
        </div>

        <button type="submit" style="background: var(--roxo-base); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer;">Publicar Aula Completa</button>
      </form>
    </div>
  </div>
</body>
</html>
