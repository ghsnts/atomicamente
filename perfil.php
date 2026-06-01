<?php
session_start();
require_once 'config.php'; // Garante que a conexão com a base de dados ($pdo) está aqui

// Proteção básica: apenas utilizadores logados
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$mensagem = "";
$erro = "";

// 1. BUSCAR AS PREFERÊNCIAS ATUAIS NA BASE DE DADOS
try {
    $stmt = $pdo->prepare("SELECT meta_diaria, frente_foco FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $usuario_dados = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o aluno ainda não tiver preferências, assume estes valores padrão
    $meta_atual = $usuario_dados['meta_diaria'] ?? 20;
    $frente_atual = $usuario_dados['frente_foco'] ?? '';
} catch (PDOException $e) {
    $erro = "Erro ao carregar dados: " . $e->getMessage();
    $meta_atual = 20;
    $frente_atual = '';
}

// 2. PROCESSAR A GRAVAÇÃO QUANDO O FORMULÁRIO FOR SUBMETIDO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meta_diaria = filter_input(INPUT_POST, 'meta_diaria', FILTER_VALIDATE_INT);
    $frente_foco = filter_input(INPUT_POST, 'frente_foco', FILTER_DEFAULT);

    try {
        $stmt_update = $pdo->prepare("UPDATE users SET meta_diaria = :meta, frente_foco = :frente WHERE id = :id");
        $stmt_update->execute([
            'meta' => $meta_diaria,
            'frente' => $frente_foco,
            'id' => $user_id
        ]);

        $mensagem = "Preferências atualizadas com sucesso!";
        
        // Atualiza as variáveis locais para que o HTML mostre a nova seleção imediatamente
        $meta_atual = $meta_diaria;
        $frente_atual = $frente_foco;
    } catch (PDOException $e) {
        $erro = "Erro ao salvar as configurações: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meu Perfil | Atomicamente</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  <script src="js/tema.js"></script>
  <style>
    .perfil-container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
    .card-perfil { background: var(--bg-card); border-radius: 16px; border: 1px solid var(--borda); padding: 30px; margin-bottom: 25px; transition: background 0.2s; }
    .perfil-header { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--borda); }
    .avatar-grande { width: 80px; height: 80px; border-radius: 50%; background: var(--roxo-base); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; }
    
    .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; }
    .form-group label { font-size: 0.9rem; font-weight: 600; color: var(--texto-secundario); }
    .form-control { padding: 12px; border: 1px solid var(--borda); border-radius: 8px; background: transparent; color: var(--texto-principal); font-family: 'Inter', sans-serif; font-size: 0.95rem; width: 100%; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--roxo-base); }
    .form-control:disabled { opacity: 0.5; cursor: not-allowed; background: rgba(0,0,0,0.02); }
    
    .btn-salvar { background: var(--roxo-base); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; width: 100%; font-size: 0.95rem; }
    .btn-salvar:hover { background: #6d28d9; }
    
    .alerta { padding: 15px; border-radius: 8px; font-size: 0.95rem; margin-bottom: 20px; font-weight: 500; }
    .alerta-sucesso { background: #ecfdf5; border: 1px solid #10b981; color: #065f46; }
    .alerta-erro { background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; }
  </style>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem" style="background: var(--roxo-base);">MEU PERFIL</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.88rem; margin-right: 5px;">← Voltar ao Painel</a>
        
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
            🛠️ Configurações
          </button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()">
              <span id="btn-tema-texto">🌙 Modo Escuro</span>
            </div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 14px; font-size: 0.88rem; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
            👤 <?php echo explode(' ', $_SESSION['user_nome'] ?? 'Estudante')[0]; ?> <span style="font-size: 0.65rem;">▼</span>
          </button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <a href="logout.php" class="dropdown-item sair">🚪 Sair da Conta</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="perfil-container">
    
    <?php if (!empty($mensagem)): ?>
      <div class="alerta alerta-sucesso"><?php echo $mensagem; ?></div>
    <?php endif; ?>
    <?php if (!empty($erro)): ?>
      <div class="alerta alerta-erro"><?php echo $erro; ?></div>
    <?php endif; ?>

    <div class="card-perfil">
      <div class="perfil-header">
        <div class="avatar-grande">
          <?php echo strtoupper(substr($_SESSION['user_nome'] ?? 'A', 0, 1)); ?>
        </div>
        <div>
          <h1 style="margin: 0; font-size: 1.5rem;">Configurações da Conta</h1>
          <p style="margin: 5px 0 0; color: var(--texto-secundario);">Gerencia os teus dados de acesso e métricas de foco.</p>
        </div>
      </div>

      <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
          <div class="form-group">
            <label>Nome Completo</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_nome'] ?? ''); ?>" disabled>
          </div>
          
          <div class="form-group">
            <label>Email de Acesso</label>
            <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" disabled>
          </div>
        </div>

        <h3 style="margin-top: 30px; border-bottom: 1px solid var(--borda); padding-bottom: 10px; margin-bottom: 20px;">🎯 Planeamento de Estudos</h3>
        
        <div class="form-group">
          <label>Qual é a tua meta diária de exercícios?</label>
          <select name="meta_diaria" class="form-control">
            <option value="10" <?php echo $meta_atual == 10 ? 'selected' : ''; ?>>10 Questões (Aquecimento)</option>
            <option value="20" <?php echo $meta_atual == 20 ? 'selected' : ''; ?>>20 Questões (Foco Recomendado)</option>
            <option value="40" <?php echo $meta_atual == 40 ? 'selected' : ''; ?>>40 Questões (Intensivo)</option>
            <option value="90" <?php echo $meta_atual == 90 ? 'selected' : ''; ?>>90 Questões (Simulado)</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 30px;">
          <label>Frente Académica Focus (Maior Dificuldade)</label>
          <select name="frente_foco" class="form-control">
            <option value="" <?php echo $frente_atual == '' ? 'selected' : ''; ?>>Nenhuma selecionada</option>
            <option value="natureza" <?php echo $frente_atual == 'natureza' ? 'selected' : ''; ?>>Ciências da Natureza</option>
            <option value="matematica" <?php echo $frente_atual == 'matematica' ? 'selected' : ''; ?>>Matemática</option>
            <option value="humanas" <?php echo $frente_atual == 'humanas' ? 'selected' : ''; ?>>Ciências Humanas</option>
            <option value="linguagens" <?php echo $frente_atual == 'linguagens' ? 'selected' : ''; ?>>Linguagens e Códigos</option>
          </select>
        </div>

        <button type="submit" class="btn-salvar">Gravar Preferências</button>
      </form>
    </div>
  </main>

</body>
</html>
