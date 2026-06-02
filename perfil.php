<?php
session_start();
require_once 'config.php';

// Proteção: Garante que o utilizador está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensagem_sucesso = "";
$mensagem_erro = "";

// 1. PROCESSAR O ENVIO DO FORMULÁRIO (GRAVAÇÃO)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meta_diaria = filter_input(INPUT_POST, 'meta_diaria', FILTER_VALIDATE_INT);
    $frente_foco = filter_input(INPUT_POST, 'frente_foco', FILTER_DEFAULT);

    // Validações básicas de segurança
    if ($meta_diaria === false || $meta_diaria < 1) {
        $meta_diaria = 20; // Fallback seguro
    }
    
    // Validar se a frente enviada pertence ao escopo de Química do site
    $frentes_validas = ['geral', 'fisico', 'organica', 'ambiental', ''];
    if (!in_array($frente_foco, $frentes_validas)) {
        $frente_foco = '';
    }

    try {
        $stmtUpdate = $pdo->prepare("UPDATE users SET meta_diaria = :meta, frente_foco = :foco WHERE id = :uid");
        $stmtUpdate->execute([
            ':meta' => $meta_diaria,
            ':foco' => $frente_foco,
            ':uid'  => $user_id
        ]);
        $mensagem_sucesso = "Preferências de Química atualizadas com sucesso! 🧪";
    } catch (PDOException $e) {
        $mensagem_erro = "Erro ao salvar no banco de dados: " . $e->getMessage();
    }
}

// 2. BUSCAR DADOS ATUAIS DO ALUNO PARA PREENCHER O FORMULÁRIO
try {
    // Alterado 'name' para 'nome' para bater com o padrão da tua base de dados
    $stmtUser = $pdo->prepare("SELECT nome, email, meta_diaria, frente_foco FROM users WHERE id = :uid");
    $stmtUser->execute([':uid' => $user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $meta_atual = $user['meta_diaria'] ?? 20;
    $frente_atual = $user['frente_foco'] ?? '';
} catch (PDOException $e) {
    die("Erro ao carregar perfil: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Preferências do Perfil | Atomicamente</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    .perfil-container { max-width: 650px; margin: 40px auto; padding: 0 20px; }
    
    .card-perfil {
      background-color: var(--bg-card);
      border: 1px solid var(--borda);
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .form-group { margin-bottom: 20px; }
    .form-label {
      display: block;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--texto-principal);
      margin-bottom: 8px;
    }

    .form-input, .form-select {
      width: 100%;
      padding: 12px;
      border: 1px solid var(--borda);
      background-color: var(--bg-global);
      color: var(--texto-principal);
      border-radius: 8px;
      font-size: 0.95rem;
      font-family: inherit;
      box-sizing: border-box;
      transition: border-color 0.2s;
    }
    .form-input:focus, .form-select:focus {
      border-color: var(--roxo-base);
      outline: none;
    }
    .form-input[disabled] {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .alert { padding: 12px 16px; border-radius: 8px; font-size: 0.9rem; font-weight: 500; margin-bottom: 20px; }
    .alert-sucesso { background-color: var(--sucesso-fundo); color: var(--sucesso); border: 1px solid rgba(16, 185, 129, 0.2); }
    .alert-erro { background-color: #fef2f2; color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }

    .btn-salvar {
      background: var(--roxo-base);
      color: white;
      border: none;
      padding: 14px 20px;
      font-size: 0.95rem;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      transition: background 0.2s;
    }
    .btn-salvar:hover { background: var(--roxo-vivo); }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem">ENEM</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.88rem; margin-right: 5px;">← Voltar ao Painel</a>
        
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 8px; font-weight: 600; cursor: pointer;">🛠️ Configurações</button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()"><span id="btn-tema-texto">🌙 Modo Escuro</span></div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="perfil-container">
    <div class="card-perfil">
      <h2 style="margin: 0 0 8px 0; font-size: 1.4rem; color: var(--texto-principal);">Configurações do Perfil</h2>
      <p style="margin: 0 0 25px 0; color: var(--texto-secundario); font-size: 0.9rem;">Personaliza a tua rota de aprendizagem focada no ENEM.</p>

      <?php if (!empty($mensagem_sucesso)): ?>
          <div class="alert alert-sucesso"><?php echo $mensagem_sucesso; ?></div>
      <?php endif; ?>
      <?php if (!empty($mensagem_erro)): ?>
          <div class="alert alert-erro"><?php echo $mensagem_erro; ?></div>
      <?php endif; ?>

      <form action="perfil.php" method="POST">
        
        <div class="form-group">
  <label class="form-label">Teu Nome</label>
  <input type="text" class="form-input" value="<?php echo htmlspecialchars($user['nome'] ?? $_SESSION['user_nome'] ?? 'Estudante'); ?>" disabled />
</div>

        <div class="form-group">
          <label class="form-label">Endereço de E-mail</label>
          <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled />
        </div>

        <hr style="border: 0; height: 1px; background: var(--borda); margin: 25px 0;" />
        <h3 style="margin: 0 0 15px 0; font-size: 1.1rem; color: var(--roxo-base);">🎯 Metas & Foco Pedagógico</h3>

        <div class="form-group">
          <label class="form-label" for="meta_diaria">Meta Diária de Questões</label>
          <input type="number" id="meta_diaria" name="meta_diaria" class="form-input" min="1" max="200" value="<?php echo $meta_atual; ?>" required />
          <small style="color: var(--texto-secundario); font-size: 0.8rem; display: block; margin-top: 4px;">Recomendamos de 15 a 30 questões para manter a consistência.</small>
        </div>

        <div class="form-group">
          <label class="form-label" for="frente_foco">Frente de Química com Maior Dificuldade</label>
          <select id="frente_foco" name="frente_foco" class="form-select">
            <option value="" <?php echo $frente_atual == '' ? 'selected' : ''; ?>>Nenhuma selecionada (Recomendações gerais)</option>
            <option value="geral" <?php echo $frente_atual == 'geral' ? 'selected' : ''; ?>>Química Geral e Atomística</option>
            <option value="fisico" <?php echo $frente_atual == 'fisico' ? 'selected' : ''; ?>>Físico-Química (Cálculos e Soluções)</option>
            <option value="organica" <?php echo $frente_atual == 'organica' ? 'selected' : ''; ?>>Química Orgânica (Cadeias e Funções)</option>
            <option value="ambiental" <?php echo $frente_atual == 'ambiental' ? 'selected' : ''; ?>>Química Ambiental e Cotidiano</option>
          </select>
          <small style="color: var(--texto-secundario); font-size: 0.8rem; display: block; margin-top: 4px;">O teu Painel Inicial vai sugerir tópicos prioritários com base nesta escolha.</small>
        </div>

        <button type="submit" class="btn-salvar">Salvar Preferências</button>
      </form>
    </div>
  </main>

  <script>
    function alternarDropdown(id) {
        document.getElementById(id).classList.toggle('mostrar');
    }
    window.onclick = function(event) {
        if (!event.target.matches('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => drop.classList.remove('mostrar'));
        }
    }
  </script>
</body>
</html>
