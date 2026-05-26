<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="css/plataforma.css">
</head>
<body class="dash-body">
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="index.php" class="marca-dash">⚛️ Atomicamente <small class="badge-admin" style="background:#2563eb; color:white; padding:2px 6px; border-radius:4px; font-size:0.75rem;">ADMIN</small></a>
      <nav class="abas-navegacao-topo">
        <a href="dashboard.php" class="nav-link-topo">📊 Ver como Aluna</a>
        <a href="admin_dashboard.php" class="nav-link-topo active">🎛️ Gestão</a>
      </nav>
    </div>
  </header>

  <div class="container" style="padding: 40px 0;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <a href="admin_conteudo.php" style="background: white; border: 1px solid var(--borda); padding: 30px; border-radius: 16px; text-decoration: none; color: var(--roxo-base); font-weight: bold; text-align: center; font-size: 1.2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">✍️ Elaborar Texto, Vídeo e Fontes</a>
      <a href="admin_questoes.php" style="background: white; border: 1px solid var(--borda); padding: 30px; border-radius: 16px; text-decoration: none; color: #16a34a; font-weight: bold; text-align: center; font-size: 1.2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">📝 Adicionar Novas Questões ao Banco</a>
    </div>
  </div>
</body>
</html>
