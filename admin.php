<?php 
session_start();
// Comentar estas linhas se quiser testar sem login real
// if(!isset($_SESSION['usuario_id']) || $_SESSION['role_id'] != 1) {
//     header("Location: index.php");
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="icon" href="assets/favicon.ico" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css" />
</head>
<body class="dash-body">
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="index.php" class="marca-dash">Atomicamente <small class="badge-admin">ADMIN</small></a>
      <div class="user-info">
        <a href="topico.php" class="admin-link">👁️ Ver Trilha</a>
        <span class="badge-role" style="background:#4b1d73; color:white;">Admin</span>
      </div>
    </div>
  </header>

  <div class="container workspace-layout" style="grid-template-columns: 1fr;">
    <main class="conteudo-principal">
      <div class="header-topico">
        <h1>Painel de Gestão e Criação de Conteúdo</h1>
        <p>Gira as permissões de acesso das estudantes e configura o banco de questões por sub-assunto do ENEM.</p>
      </div>

      <div class="card-teoria" style="margin-top: 20px;">
        <h3>📝 Cadastrar Nova Questão do ENEM</h3>
        <form style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
           <input type="text" placeholder="Enunciado da Questão..." style="padding: 10px; border-radius: 8px; border: 1px solid #ccc; width: 100%;" />
           <button type="button" class="btn-primario" onclick="alert('Questão enviada para persistência PDO!')">Guardar no Banco de Dados</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
