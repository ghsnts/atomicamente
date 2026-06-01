<?php
session_start();
require_once 'config.php';

// Proteção básica: apenas utilizadores logados
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Simulador de atualização de dados
$mensagem = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aqui no futuro faremos o UPDATE no banco de dados
    $mensagem = "Preferências atualizadas com sucesso!";
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
    .form-control { padding: 12px; border: 1px solid var(--borda); border-radius: 8px; background: transparent; color: var(--texto-principal); font-family: 'Inter', sans-serif; font-size: 0.95rem; }
    .form-control:focus { outline: none; border-color: var(--roxo-base); }
    .form-control:disabled { opacity: 0.6; cursor: not-allowed; }
    
    .btn-salvar { background: var(--roxo-base); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; width: 100%; }
    .btn-salvar:hover { background: #6d28d9; }
    
    .alerta { padding: 15px; border-radius: 8px; font-size: 0.95rem; margin-bottom: 20px; font-weight: 500; background: #ecfdf5; border: 1px solid #10b981; color: #065f46; }
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
      <div class="alerta"><?php echo $mensagem; ?></div>
    <?php endif; ?>

    <div class="card-perfil">
      <div class="perfil-header">
        <div class="avatar-grande">
          <?php echo strtoupper(substr($_SESSION['user_nome'] ?? 'A', 0, 1)); ?>
        </div>
        <div>
          <h1 style="margin: 0; color: var(--roxo-profundo); font-size: 1.5rem;">Configurações da Conta</h1>
          <p style="margin: 5px 0 0; color: var(--texto-secundario);">Gere os teus dados pessoais e preferências de estudo.</p>
        </div>
      </div>

      <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="form-group">
            <label>Nome Completo</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_nome'] ?? ''); ?>" disabled>
            <span style="font-size: 0.8rem; color: var(--texto-secundario);">* O nome não pode ser alterado.</span>
          </div>
          
          <div class="form-group">
            <label>Email de Acesso</label>
            <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" disabled>
          </div>
        </div>

        <h3 style="margin-top: 30px; border-bottom: 1px solid var(--borda); padding-bottom: 10px; color: var(--roxo-profundo);">Meta de Estudos (ENEM)</h3>
        
        <div class="form-group">
          <label>Qual é a tua meta diária de exercícios?</label>
          <select name="meta_diaria" class="form-control">
            <option value="10">10 Questões (Aquecimento)</option>
            <option value="20" selected>20 Questões (Foco Ideal)</option>
            <option value="40">40 Questões (Intensivo)</option>
            <option value="90">90 Questões (Simulado Parcial)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Frente com maior dificuldade (Prioridade)</label>
          <select name="frente_foco" class="form-control">
            <option value="natureza">Ciências da Natureza</option>
            <option value="matematica">Matemática</option>
            <option value="humanas">Ciências Humanas</option>
            <option value="linguagens">Linguagens</option>
          </select>
        </div>

        <button type="submit" class="btn-salvar">Salvar Preferências</button>
      </form>
    </div>
  </main>

</body>
</html>
