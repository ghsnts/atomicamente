<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastrar Questões | Admin</title>
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
      <h2>📝 Adicionar Questão ao Banco de Fixação</h2>
      
      <form action="#" method="POST" style="display: flex; flex-direction: column; gap: 20px; margin-top: 20px;" onsubmit="alert('Questão e alternativas salvas na base de dados!'); return false;">
        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Vincular ao Subtópico:</label>
          <select style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);">
            <option value="modelos-atomicos">Modelos Atómicos e Distribuição</option>
            <option value="estequiometria">Estequiometria</option>
          </select>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Enunciado da Questão:</label>
          <textarea rows="4" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);" placeholder="Digite o enunciado completo estilo ENEM..." required></textarea>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
          <label style="font-weight: bold;">Alternativas (Marque a bola da correta):</label>
          <div style="display: flex; gap: 10px; align-items: center;"><input type="radio" name="correta" required> <input type="text" placeholder="Texto da Alternativa A" style="flex:1; padding: 8px; border-radius: 6px; border: 1px solid var(--borda);" required></div>
          <div style="display: flex; gap: 10px; align-items: center;"><input type="radio" name="correta"> <input type="text" placeholder="Texto da Alternativa B" style="flex:1; padding: 8px; border-radius: 6px; border: 1px solid var(--borda);" required></div>
          <div style="display: flex; gap: 10px; align-items: center;"><input type="radio" name="correta"> <input type="text" placeholder="Texto da Alternativa C" style="flex:1; padding: 8px; border-radius: 6px; border: 1px solid var(--borda);" required></div>
          <div style="display: flex; gap: 10px; align-items: center;"><input type="radio" name="correta"> <input type="text" placeholder="Texto da Alternativa D" style="flex:1; padding: 8px; border-radius: 6px; border: 1px solid var(--borda);" required></div>
          <div style="display: flex; gap: 10px; align-items: center;"><input type="radio" name="correta"> <input type="text" placeholder="Texto da Alternativa E" style="flex:1; padding: 8px; border-radius: 6px; border: 1px solid var(--borda);" required></div>
        </div>

        <button type="submit" style="background: #16a34a; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer;">Publicar no Banco</button>
      </form>
    </div>
  </div>
</body>
</html>
