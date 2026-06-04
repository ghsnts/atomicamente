<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$primeiro_nome = explode(' ', trim($_SESSION['user_nome'] ?? 'Estudante'))[0];

try {
    $stmtStreak = $pdo->prepare("SELECT streak FROM users WHERE id = :uid");
    $stmtStreak->execute([':uid' => $user_id]);
    $streak_aluno = $stmtStreak->fetchColumn() ?: 0;

    $stmtMapeamento = $pdo->query("
        SELECT 
            f.id as frente_id, f.nome as frente_nome, 
            t.id as topico_id, t.nome as topico_nome,
            (SELECT COUNT(*) FROM questions q WHERE q.subtopic_id = t.id) as qtd_questoes
        FROM frentes f
        JOIN topicos t ON f.id = t.frente_id
        ORDER BY f.id ASC, t.id ASC
    ");
    
    $frentes_agrupadas = [];
    while ($row = $stmtMapeamento->fetch(PDO::FETCH_ASSOC)) {
        $frentes_agrupadas[$row['frente_id']]['nome'] = $row['frente_nome'];
        $frentes_agrupadas[$row['frente_id']]['topicos'][] = [
            'id' => $row['topico_id'],
            'nome' => $row['topico_nome'],
            'qtd' => $row['qtd_questoes']
        ];
    }
} catch (PDOException $e) {
    die("Erro ao carregar configurações: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerador de Simulados | Atomicamente</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); margin: 0; }
    
    .topo-dash { border-bottom: 1px solid var(--borda); background: var(--bg-card); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .nav-dash { padding: 12px 20px; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; }
    .marca-dash { font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--texto-principal); letter-spacing: -0.03em; }

    .container-simulado { max-width: 900px; margin: 50px auto; padding: 0 20px; }
    
    .hero-simulado { text-align: center; margin-bottom: 50px; }
    .titulo-hero { font-size: 2.8rem; font-weight: 800; letter-spacing: -0.04em; margin: 0 0 15px 0; color: var(--texto-principal); }
    .subtitulo-hero { font-size: 1.15rem; color: var(--texto-secundario); margin: 0 auto; max-width: 600px; line-height: 1.6; }

    .secao-config { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 24px; padding: 40px; margin-bottom: 30px; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.02); }
    .titulo-secao { font-size: 1.4rem; font-weight: 800; margin: 0 0 25px 0; letter-spacing: -0.02em; }
    
    .grid-cards { display: flex; gap: 15px; flex-wrap: wrap; }
    .radio-card { display: none; }
    .label-card { flex: 1; min-width: 110px; text-align: center; padding: 20px 10px; background: var(--bg-global); border: 2px solid var(--borda); border-radius: 16px; cursor: pointer; transition: all 0.2s ease; font-weight: 800; font-size: 1.3rem; color: var(--texto-secundario); display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 5px; }
    .label-card:hover { border-color: rgba(139, 92, 246, 0.4); background: var(--roxo-suave); }
    .radio-card:checked + .label-card { border-color: var(--roxo-base); background: var(--roxo-suave); color: var(--roxo-base); box-shadow: 0 4px 15px rgba(139, 92, 246, 0.2); transform: translateY(-2px); }
    .desc-card { font-size: 0.8rem; font-weight: 600; color: var(--texto-secundario); }
    .radio-card:checked + .label-card .desc-card { color: var(--roxo-base); opacity: 0.8; }

    .frente-bloco { margin-bottom: 30px; border-bottom: 1px solid var(--borda); padding-bottom: 30px; }
    .frente-bloco:last-child { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
    
    .frente-topo { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .frente-titulo { font-size: 1.2rem; font-weight: 800; color: var(--texto-principal); display: flex; align-items: center; gap: 10px; }
    .btn-selecionar-todos { background: none; border: 1px solid var(--borda); color: var(--texto-secundario); padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.2s; }
    .btn-selecionar-todos:hover { border-color: var(--roxo-base); color: var(--roxo-base); }

    .grid-topicos { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
    .topico-item { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 1px solid var(--borda); border-radius: 12px; cursor: pointer; transition: all 0.2s; background: var(--bg-global); }
    .topico-item:hover { border-color: var(--roxo-base); }
    .topico-item.desativado { opacity: 0.5; cursor: not-allowed; border-color: var(--borda); background: rgba(0,0,0,0.02); }
    
    .check-custom { width: 20px; height: 20px; accent-color: var(--roxo-base); cursor: pointer; }
    .topico-info { display: flex; flex-direction: column; }
    .topico-nome { font-weight: 600; font-size: 0.95rem; color: var(--texto-principal); }
    .topico-badge { font-size: 0.75rem; color: var(--texto-secundario); font-weight: 500; margin-top: 2px; }

    .barra-acao-fixa { position: sticky; bottom: 30px; display: flex; flex-direction: column; align-items: center; z-index: 10; margin-top: 40px; background: var(--bg-card); padding: 20px; border-radius: 20px; border: 1px solid var(--borda); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .contador-info { margin-bottom: 15px; font-size: 0.95rem; color: var(--texto-secundario); font-weight: 600; }
    .contador-info strong { color: var(--roxo-base); font-size: 1.1rem; }

    .btn-gerar { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; padding: 18px 50px; font-size: 1.2rem; font-weight: 800; border-radius: 50px; cursor: pointer; box-shadow: 0 10px 25px rgba(79, 70, 229, 0.4); transition: all 0.3s ease; width: 100%; max-width: 400px; }
    .btn-gerar:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 35px rgba(79, 70, 229, 0.5); }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 34px; border-radius: 8px;" />
        Atomicamente <span class="badge-enem" style="font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; color: white; background: #ea580c;">MODO PROVA</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 6px; background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.2); padding: 8px 14px; border-radius: 12px; font-weight: 800; color: #ea580c; font-size: 0.95rem;">
          🔥 <?php echo $streak_aluno; ?> Dias
        </div>
        
        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 700; font-size: 0.95rem; margin-right: 10px;">Voltar ao Início</a>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 8px; font-weight: 600; cursor: pointer;">🛠️ Configs</button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()"><span id="btn-tema-texto">🌙 Modo Escuro</span></div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <form action="prova.php" method="POST" id="formSimulado">
    <div class="container-simulado">
      
      <div class="hero-simulado">
        <h1 class="titulo-hero">Configure o seu Simulado</h1>
        <p class="subtitulo-hero">Escolha o tamanho do desafio, defina o tempo e filtre os tópicos. A sua arena de batalha será gerada imediatamente.</p>
      </div>

      <div class="secao-config">
        <h2 class="titulo-secao">1. Qual o tamanho da prova?</h2>
        <div class="grid-cards">
          <?php foreach ([10, 15, 20, 25, 30, 50] as $idx => $qtd): ?>
            <input type="radio" name="qtd_questoes" id="qtd_<?php echo $qtd; ?>" value="<?php echo $qtd; ?>" class="radio-card" <?php echo $idx === 0 ? 'checked' : ''; ?>>
            <label for="qtd_<?php echo $qtd; ?>" class="label-card"><?php echo $qtd; ?></label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="secao-config">
        <h2 class="titulo-secao">2. Defina o Ritmo (Tempo)</h2>
        <div class="grid-cards">
          <input type="radio" name="ritmo_tempo" id="ritmo_padrao" value="3" class="radio-card" checked>
          <label for="ritmo_padrao" class="label-card">
            Padrão ENEM
            <span class="desc-card">3 min / questão</span>
          </label>

          <input type="radio" name="ritmo_tempo" id="ritmo_sprint" value="1.5" class="radio-card">
          <label for="ritmo_sprint" class="label-card">
            Sprint
            <span class="desc-card">1.5 min / questão</span>
          </label>

          <input type="radio" name="ritmo_tempo" id="ritmo_relax" value="5" class="radio-card">
          <label for="ritmo_relax" class="label-card">
            Relaxado
            <span class="desc-card">5 min / questão</span>
          </label>

          <input type="radio" name="ritmo_tempo" id="ritmo_livre" value="0" class="radio-card">
          <label for="ritmo_livre" class="label-card">
            Modo Zen
            <span class="desc-card">Sem limite de tempo</span>
          </label>
        </div>
      </div>

      <div class="secao-config">
        <h2 class="titulo-secao">3. O que deseja incluir?</h2>
        <?php foreach ($frentes_agrupadas as $frente_id => $frente): ?>
          <div class="frente-bloco">
            <div class="frente-topo">
              <span class="frente-titulo">📚 <?php echo htmlspecialchars($frente['nome']); ?></span>
              <button type="button" class="btn-selecionar-todos" onclick="toggleFrente(<?php echo $frente_id; ?>, this)">Desmarcar Tudo</button>
            </div>
            
            <div class="grid-topicos" id="grid_frente_<?php echo $frente_id; ?>">
              <?php foreach ($frente['topicos'] as $topico): ?>
                <?php $desativado = $topico['qtd'] == 0; ?>
                <label class="topico-item <?php echo $desativado ? 'desativado' : ''; ?>">
                  <input type="checkbox" name="topicos[]" value="<?php echo $topico['id']; ?>" data-qtd="<?php echo $topico['qtd']; ?>" class="check-custom checkbox-frente-<?php echo $frente_id; ?>" <?php echo $desativado ? 'disabled' : 'checked'; ?>>
                  <div class="topico-info">
                    <span class="topico-nome"><?php echo htmlspecialchars($topico['nome']); ?></span>
                    <span class="topico-badge"><?php echo $desativado ? 'Sem questões' : $topico['qtd'] . ' disponíveis'; ?></span>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="barra-acao-fixa">
        <div class="contador-info" id="contadorDisponivel">
          Calculando questões disponíveis...
        </div>
        <button type="submit" class="btn-gerar" id="btnSubmit">⏳ Entrar na Arena de Prova</button>
      </div>
    </div>
  </form>

  <script>
    // Atualiza o contador de questões disponíveis em tempo real
    function calcularDisponiveis() {
        const checkboxesMarcados = document.querySelectorAll('input[name="topicos[]"]:checked');
        let totalDisponivel = 0;
        
        checkboxesMarcados.forEach(cb => {
            totalDisponivel += parseInt(cb.getAttribute('data-qtd')) || 0;
        });

        document.getElementById('contadorDisponivel').innerHTML = `Existem <strong>${totalDisponivel} questões</strong> disponíveis nas matérias selecionadas.`;
        return totalDisponivel;
    }

    // Chama a função ao carregar a página e toda vez que um checkbox for alterado
    window.onload = calcularDisponiveis;
    document.querySelectorAll('input[name="topicos[]"]').forEach(cb => {
        cb.addEventListener('change', calcularDisponiveis);
    });

    // Função de marcar/desmarcar tudo
    function toggleFrente(frenteId, btnElement) {
        const checkboxes = document.querySelectorAll('.checkbox-frente-' + frenteId + ':not(:disabled)');
        const marcados = document.querySelectorAll('.checkbox-frente-' + frenteId + ':checked:not(:disabled)').length;
        if (marcados === checkboxes.length) {
            checkboxes.forEach(cb => cb.checked = false);
            btnElement.innerText = "Selecionar Tudo";
        } else {
            checkboxes.forEach(cb => cb.checked = true);
            btnElement.innerText = "Desmarcar Tudo";
        }
        calcularDisponiveis(); // Atualiza o contador após o clique em massa
    }
    
    // A TRAVA DE SEGURANÇA NA HORA DE ENVIAR
    document.getElementById('formSimulado').addEventListener('submit', function(e) {
        const totalDisponivel = calcularDisponiveis();
        const qtdDesejada = parseInt(document.querySelector('input[name="qtd_questoes"]:checked').value);

        if (document.querySelectorAll('input[name="topicos[]"]:checked').length === 0) {
            e.preventDefault(); 
            alert('⚠️ Atenção: Precisas selecionar pelo menos um tópico para gerar a prova!');
            return;
        }

        if (totalDisponivel < qtdDesejada) {
            e.preventDefault();
            alert(`⚠️ Atenção: Você selecionou uma prova de ${qtdDesejada} questões, mas as matérias escolhidas possuem apenas ${totalDisponivel} questões cadastradas no banco de dados.\n\nPor favor, marque mais matérias ou diminua o tamanho da prova para continuar.`);
        }
    });

    function alternarDropdown(id) {
        document.querySelectorAll('.dropdown-conteudo').forEach(drop => { if(drop.id !== id) drop.classList.remove('mostrar'); });
        document.getElementById(id).classList.toggle('mostrar');
    }
    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => drop.classList.remove('mostrar'));
        }
    }
  </script>
</body>
</html>
