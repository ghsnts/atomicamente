<header class="topo-dash">
    <div class="container nav-dash" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
      
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente 
        <?php 
          // Ajusta a Badge dinamicamente para não precisar duplicar cabeçalhos complexos
          $pagina_atual = basename($_SERVER['PHP_SELF']);
          if ($pagina_atual === 'topico.php') {
              echo '<span class="badge-enem" style="background: var(--roxo-base);">SALA DE AULA</span>';
          } elseif ($pagina_atual === 'admin.php') {
              echo '<span class="badge-enem" style="background: #ef4444;">PAINEL ADMIN</span>';
          } else {
              echo '<span class="badge-enem">ENEM</span>';
          }
        ?>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        
        <?php if (verificarSeEhAdmin() && $pagina_atual !== 'admin.php'): ?>
          <a href="admin.php" class="btn-acao" style="background: #7c3aed; color: white; padding: 8px 14px; font-size: 0.82rem; border-radius: 8px; text-decoration: none; font-weight: 700; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);">
            ⚙️ Gerenciar
          </a>
        <?php endif; ?>

        <?php if ($pagina_atual === 'admin.php' || $pagina_atual === 'topico.php'): ?>
          <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.88rem; margin-right: 5px;">Painel Inicial</a>
        <?php endif; ?>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
            🛠️ Configurações
          </button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()">
              <span id="btn-tema-texto">🌙 Modo Escuro</span>
            </div>
            <div class="dropdown-item" style="opacity: 0.6; cursor: not-allowed;">
              <span>🔔 Notificações (Breve)</span>
            </div>
            <div class="dropdown-item" style="opacity: 0.6; cursor: not-allowed;">
              <span>📏 Tamanho da Fonte (Breve)</span>
            </div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 8px 14px; font-size: 0.88rem; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
            👤 <?php echo explode(' ', $_SESSION['user_nome'] ?? 'Estudante')[0]; ?> <span style="font-size: 0.65rem;">▼</span>
          </button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <div style="padding: 10px; font-size: 0.75rem; color: var(--texto-secundario); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
              Minha Conta
            </div>
            <a href="perfil.php" class="dropdown-item">🧑‍🎓 Preferências do Perfil</a>
            <a href="progresso.php" class="dropdown-item">📈 Meu Progresso ENEM</a>
            <div class="dropdown-divisor"></div>
            <a href="logout.php" class="dropdown-item sair">🚪 Sair da Conta</a>
          </div>
        </div>

      </div>
    </div>
  </header>
