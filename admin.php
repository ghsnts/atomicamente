<?php
session_start();
require_once 'config.php';

// Proteção Avançada
if (!function_exists('verificarSeEhAdmin') || !verificarSeEhAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$mensagem = "";
$tipo_mensagem = "sucesso";

function gerarSlug($texto) {
    $texto = preg_replace('~[^\pL\d]+~u', '-', $texto);
    $texto = iconv('utf-8', 'us-ascii//TRANSLIT', $texto);
    $texto = preg_replace('~[^-\w]+~', '', $texto);
    $texto = trim($texto, '-');
    $texto = preg_replace('~-+~', '-', $texto);
    return strtolower($texto);
}

// ==========================================
// PROCESSAMENTO DE AÇÕES DO FORMULÁRIO (POST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. ADICIONAR TÓPICO
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_topico') {
            $frente_id = $_POST['frente_id'];
            $nome_topico = trim($_POST['nome_topico']);
            $slug = gerarSlug($nome_topico);

            if (!empty($nome_topico)) {
                $stmt = $pdo->prepare("INSERT INTO topicos (frente_id, nome, slug) VALUES (:fid, :nome, :slug)");
                $stmt->execute([':fid' => $frente_id, ':nome' => $nome_topico, ':slug' => $slug]);
                $mensagem = "Matéria <strong>$nome_topico</strong> adicionada com sucesso!";
            }
        }

        // 2. EXCLUIR TÓPICO
        if (isset($_POST['acao']) && $_POST['acao'] === 'del_topico') {
            $topico_id = $_POST['topico_id'];
            $stmt = $pdo->prepare("DELETE FROM topicos WHERE id = :id");
            $stmt->execute([':id' => $topico_id]);
            $mensagem = "Tópico e todos os seus subconteúdos foram removidos.";
            $tipo_mensagem = "aviso";
        }

        // 3. ADICIONAR AULA
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_aula') {
            $topico_id = $_POST['topico_id'];
            $titulo = trim($_POST['titulo_aula']);
            $video_url = trim($_POST['video_url']);
            $resumo = trim($_POST['resumo_aula']);

            if (!empty($titulo)) {
                $stmt = $pdo->prepare("INSERT INTO aulas (topico_id, titulo, video_url, resumo) VALUES (:tid, :titulo, :url, :resumo)");
                $stmt->execute([':tid' => $topico_id, ':titulo' => $titulo, ':url' => $video_url, ':resumo' => $resumo]);
                $mensagem = "Subtópico de aula integrado à grade!";
            }
        }

        // 4. CADASTRAR QUESTÃO NOVA
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_questao') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $topico_id = $_POST['topico_id'];
            $aula_id = !empty($_POST['aula_id']) ? $_POST['aula_id'] : null;
            $enunciado = trim($_POST['enunciado']);
            $correta = $_POST['correta']; 

            if (!empty($enunciado)) {
                $stmtQ = $pdo->prepare("INSERT INTO questions (subtopic_id, aula_id, enunciado) VALUES (:sid, :aid, :enunciado)");
                $stmtQ->execute([':sid' => $topico_id, ':aid' => $aula_id, ':enunciado' => $enunciado]);
                $question_id = $pdo->lastInsertId();

                $letras = ['A', 'B', 'C', 'D', 'E'];
                foreach ($letras as $letra) {
                    $texto_alt = trim($_POST['alt_' . strtolower($letra)]);
                    $eh_correta = ($correta === $letra) ? 1 : 0;
                    $stmtA = $pdo->prepare("INSERT INTO alternatives (question_id, letra, texto_alternativa, eh_correta) VALUES (:qid, :letra, :txt, :isc)");
                    $stmtA->execute([':qid' => $question_id, ':letra' => $letra, ':txt' => $texto_alt, ':isc' => $eh_correta]);
                }
                $mensagem = "Questão cadastrada com sucesso no Banco de Dados!";
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        }

        // 5. ATUALIZAR QUESTÃO EXISTENTE (EDITAR)
        if (isset($_POST['acao']) && $_POST['acao'] === 'edit_questao') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $questao_id = $_POST['questao_id'];
            $topico_id = $_POST['topico_id'];
            $aula_id = !empty($_POST['aula_id']) ? $_POST['aula_id'] : null;
            $enunciado = trim($_POST['enunciado']);
            $correta = $_POST['correta']; 

            if (!empty($enunciado)) {
                $stmtQ = $pdo->prepare("UPDATE questions SET subtopic_id = :sid, aula_id = :aid, enunciado = :enunciado WHERE id = :qid");
                $stmtQ->execute([':sid' => $topico_id, ':aid' => $aula_id, ':enunciado' => $enunciado, ':qid' => $questao_id]);

                $letras = ['A', 'B', 'C', 'D', 'E'];
                foreach ($letras as $letra) {
                    $texto_alt = trim($_POST['alt_' . strtolower($letra)]);
                    $eh_correta = ($correta === $letra) ? 1 : 0;
                    $stmtA = $pdo->prepare("UPDATE alternatives SET texto_alternativa = :txt, eh_correta = :isc WHERE question_id = :qid AND letra = :letra");
                    $stmtA->execute([':txt' => $texto_alt, ':isc' => $eh_correta, ':qid' => $questao_id, ':letra' => $letra]);
                }
                $mensagem = "Questão #$questao_id atualizada com sucesso!";
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        }

        // 6. EXCLUIR QUESTÃO
        if (isset($_POST['acao']) && $_POST['acao'] === 'del_questao') {
            $questao_id = $_POST['questao_id'];
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = :id");
            $stmt->execute([':id' => $questao_id]);
            $mensagem = "Questão excluída definitivamente da plataforma.";
            $tipo_mensagem = "aviso";
        }

    } catch (PDOException $e) {
        $mensagem = "Erro operacional no Banco de Dados: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// ==========================================
// PUXAR DADOS PARA EXIBIÇÃO E JSON
// ==========================================
$frentes = $pdo->query("SELECT * FROM frentes ORDER BY id ASC")->fetchAll();
$topicos = $pdo->query("SELECT t.*, f.nome as nome_frente FROM topicos t JOIN frentes f ON t.frente_id = f.id ORDER BY f.id, t.id ASC")->fetchAll();
$aulas   = $pdo->query("SELECT a.*, t.nome as nome_topico FROM aulas a JOIN topicos t ON a.topico_id = t.id ORDER BY t.id, a.id ASC")->fetchAll();

// Listar todas as questões com suas alternativas para o JS (Edição rápida)
$lista_questoes = $pdo->query("
    SELECT q.*, t.nome as nome_topico 
    FROM questions q 
    JOIN topicos t ON q.subtopic_id = t.id 
    ORDER BY q.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$questoes_json_data = [];
foreach ($lista_questoes as $q) {
    $stmtA = $pdo->prepare("SELECT * FROM alternatives WHERE question_id = :qid ORDER BY letra ASC");
    $stmtA->execute([':qid' => $q['id']]);
    $q['alternativas'] = $stmtA->fetchAll(PDO::FETCH_ASSOC);
    $questoes_json_data[$q['id']] = $q;
}

$total_questoes = count($lista_questoes);
$total_topicos = count($topicos);
$total_aulas = count($aulas);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Centro de Comando | Atomicamente</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); margin: 0; color: var(--texto-principal); }
    
    .topo-dash { border-bottom: 1px solid var(--borda); background: var(--bg-card); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .nav-dash { padding: 12px 20px; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; }
    .marca-dash { font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--texto-principal); letter-spacing: -0.03em; }
    .badge-enem { font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; color: white; letter-spacing: 0.05em; }

    .admin-container { max-width: 1050px; margin: 40px auto; padding: 0 20px; }
    
    .hero-admin { background: linear-gradient(135deg, #ef4444, #b91c1c); border-radius: 24px; padding: 40px 50px; color: white; box-shadow: 0 10px 30px -5px rgba(239, 68, 68, 0.4); margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
    .hero-admin h1 { margin: 0 0 10px 0; font-size: 2.2rem; font-weight: 800; letter-spacing: -0.03em; }
    .hero-admin p { margin: 0; font-size: 1.05rem; opacity: 0.9; max-width: 500px; line-height: 1.5; }
    
    .stats-admin { display: flex; gap: 20px; }
    .stat-box { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 16px; text-align: center; backdrop-filter: blur(5px); }
    .stat-num { font-size: 2.2rem; font-weight: 800; line-height: 1; margin-bottom: 5px; }
    .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; opacity: 0.9; }

    .card-admin { background: var(--bg-card); border-radius: 20px; border: 1px solid var(--borda); padding: 40px; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.02); margin-top: 20px; transition: all 0.3s ease; }
    
    .aba-nav { display: flex; gap: 15px; border-bottom: 2px solid var(--borda); margin-bottom: 30px; padding-bottom: 15px; overflow-x: auto; }
    .aba-link { padding: 12px 25px; background: var(--bg-global); border: 1px solid var(--borda); border-radius: 12px; font-weight: 700; font-size: 0.95rem; color: var(--texto-secundario); cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .aba-link:hover { border-color: #ef4444; color: #ef4444; }
    .aba-link.ativa { background: #ef4444; color: white; border-color: #ef4444; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); }
    .aba-painel { display: none; animation: fadeIn 0.4s ease; }
    .aba-painel.ativo { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; }
    .form-group label { font-size: 0.95rem; font-weight: 700; color: var(--texto-principal); }
    .form-control { padding: 14px 16px; border: 2px solid var(--borda); border-radius: 12px; background: var(--bg-global); color: var(--texto-principal); font-family: 'Inter', sans-serif; font-size: 1rem; transition: all 0.2s; }
    .form-control:focus { outline: none; border-color: var(--roxo-base); box-shadow: 0 0 0 4px var(--roxo-suave); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .btn-submit { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; padding: 16px 30px; border-radius: 12px; font-weight: 800; font-size: 1.05rem; cursor: pointer; transition: all 0.2s; box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3); width: 100%; letter-spacing: 0.02em; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(79, 70, 229, 0.4); }
    .btn-cancelar { background: var(--bg-global); color: var(--texto-secundario); border: 2px solid var(--borda); padding: 16px 30px; border-radius: 12px; font-weight: 800; font-size: 1.05rem; cursor: pointer; transition: all 0.2s; width: 100%; display: none; }
    .btn-cancelar:hover { background: #ef4444; color: white; border-color: #ef4444; }
    
    .alerta { padding: 18px 20px; border-radius: 12px; font-size: 0.95rem; margin-bottom: 25px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .alerta.sucesso { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #059669; }
    .alerta.aviso { background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: #b45309; }
    .alerta.erro { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #dc2626; }

    .opcao-admin { display: flex; align-items: center; gap: 15px; padding: 12px 15px; border: 2px solid var(--borda); border-radius: 12px; background-color: var(--bg-global); margin-bottom: 12px; transition: border 0.2s; }
    .opcao-admin:focus-within { border-color: var(--roxo-base); }
    .opcao-admin .letra { font-weight: 800; font-size: 1.1rem; color: var(--roxo-base); width: 25px; }
    .opcao-admin input[type="text"] { flex: 1; border: none; background: transparent; font-size: 1rem; color: var(--texto-principal); outline: none; }

    /* TABELAS REFINADAS */
    .tabela-gestao { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .tabela-gestao th { text-align: left; padding: 15px; background: var(--bg-global); border-bottom: 2px solid var(--borda); font-size: 0.85rem; text-transform: uppercase; color: var(--texto-secundario); font-weight: 800; }
    .tabela-gestao td { padding: 15px; border-bottom: 1px solid var(--borda); font-size: 0.95rem; color: var(--texto-principal); font-weight: 500; }
    .btn-deletar { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; font-weight: 700; padding: 8px 14px; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px;}
    .btn-deletar:hover { background: #ef4444; color: white; }
    .btn-editar { background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.2); color: var(--roxo-base); font-weight: 700; padding: 8px 14px; border-radius: 8px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 5px; }
    .btn-editar:hover { background: var(--roxo-base); color: white; }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente 
        <span class="badge-enem" style="background: #ef4444;">PAINEL ADMIN</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        <a href="dashboard.php" style="color: var(--texto-secundario); text-decoration: none; font-weight: 700; font-size: 0.95rem; margin-right: 5px;">Painel Inicial</a>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 8px 12px; font-size: 0.88rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
            🛠️ Configurações
          </button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()"><span id="btn-tema-texto">🌙 Modo Escuro</span></div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="admin-container">
    
    <div class="hero-admin">
      <div>
        <h1>Gestão da Estrutura Pedagógica</h1>
        <p>Alimente a grade do curso e mantenha o banco de questões sempre atualizado.</p>
      </div>
      <div class="stats-admin">
        <div class="stat-box">
          <div class="stat-num"><?php echo $total_topicos; ?></div>
          <div class="stat-label">Tópicos</div>
        </div>
        <div class="stat-box">
          <div class="stat-num"><?php echo $total_questoes; ?></div>
          <div class="stat-label">Questões</div>
        </div>
      </div>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="alerta <?php echo $tipo_mensagem; ?>">
        <?php echo $tipo_mensagem === 'sucesso' ? '✅' : '⚠️'; ?> <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>

    <div class="aba-nav">
      <button class="aba-link ativa" onclick="alternarAba(event, 'aba-questoes')">📝 Banco de Questões</button>
      <button class="aba-link" onclick="alternarAba(event, 'aba-materias')">📂 Matérias (Tópicos)</button>
      <button class="aba-link" onclick="alternarAba(event, 'aba-aulas')">📖 Subtópicos & Aulas</button>
    </div>

    <div id="aba-questoes" class="aba-painel ativo">
      
      <div class="card-admin" id="cardFormQuestao" style="margin-top: 0;">
        <h2 id="tituloFormQuestao" style="margin-top: 0; color: var(--texto-principal); font-weight: 800;">➕ Inserir Nova Questão</h2>
        
        <form action="admin.php" method="POST" id="formQuestao">
          <input type="hidden" name="acao" id="inputAcao" value="add_questao">
          <input type="hidden" name="questao_id" id="inputQuestaoId" value="">

          <div class="form-row">
            <div class="form-group">
              <label>Matéria (Tópico)</label>
              <select name="topico_id" id="selectTopico" class="form-control" required>
                <?php foreach ($topicos as $t): ?>
                  <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Subtópico / Aula Específica (Opcional)</label>
              <select name="aula_id" id="selectAula" class="form-control">
                <option value="">Apenas vincular ao Tópico Geral</option>
                <?php foreach ($aulas as $a): ?>
                  <option value="<?php echo $a['id']; ?>"><?php echo "[".htmlspecialchars($a['nome_topico'])."] - ".htmlspecialchars($a['titulo']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Enunciado Completo da Questão</label>
            <textarea name="enunciado" id="textoEnunciado" class="form-control" rows="5" placeholder="Insira o texto base e o comando da questão aqui..." required></textarea>
          </div>

          <h3 style="color: var(--texto-principal); margin: 30px 0 15px 0; font-weight: 800;">Texto das Alternativas</h3>
          <?php foreach (['A', 'B', 'C', 'D', 'E'] as $letra): ?>
            <div class="opcao-admin">
              <span class="letra"><?php echo $letra; ?>)</span>
              <input type="text" name="alt_<?php echo strtolower($letra); ?>" id="alt_<?php echo strtolower($letra); ?>" placeholder="Texto da alternativa <?php echo $letra; ?>..." required>
            </div>
          <?php endforeach; ?>

          <div class="form-group" style="margin-top: 25px;">
            <label style="color: #10b981; font-size: 1.1rem;">Qual é a Alternativa Correta?</label>
            <select name="correta" id="selectCorreta" class="form-control" style="border-color: #10b981; border-width: 2px; font-weight: 700; color: #065f46; background: #ecfdf5;" required>
              <option value="A">Alternativa A</option>
              <option value="B">Alternativa B</option>
              <option value="C">Alternativa C</option>
              <option value="D">Alternativa D</option>
              <option value="E">Alternativa E</option>
            </select>
          </div>

          <div class="form-row" style="margin-top: 25px;">
            <button type="submit" class="btn-submit" id="btnSalvarQuestao">🚀 Publicar Questão</button>
            <button type="button" class="btn-cancelar" id="btnCancelarEdicao" onclick="cancelarEdicao()">Cancelar Edição</button>
          </div>
        </form>
      </div>

      <div class="card-admin">
        <h2 style="margin-top: 0; color: var(--texto-principal); font-weight: 800;">📋 Questões Cadastradas</h2>
        <p style="color: var(--texto-secundario); margin-top: -10px;">Visualiza, edita ou exclui questões diretamente do banco.</p>
        
        <div style="overflow-x: auto;">
          <table class="tabela-gestao">
            <thead>
              <tr>
                <th style="width: 60px;">ID</th>
                <th style="width: 200px;">Matéria</th>
                <th>Enunciado</th>
                <th style="width: 180px; text-align: right;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lista_questoes as $q): ?>
                <tr>
                  <td><strong>#<?php echo $q['id']; ?></strong></td>
                  <td><span style="background: var(--bg-global); border: 1px solid var(--borda); padding: 4px 8px; border-radius: 6px; font-size: 0.85rem; font-weight: 700; color: var(--roxo-base);"><?php echo htmlspecialchars($q['nome_topico']); ?></span></td>
                  <td><?php echo mb_strimwidth(htmlspecialchars($q['enunciado']), 0, 80, '...'); ?></td>
                  <td style="text-align: right;">
                    <button type="button" class="btn-editar" onclick="ativarEdicaoQuestao(<?php echo $q['id']; ?>)">✏️ Editar</button>
                    
                    <form action="admin.php" method="POST" onsubmit="return confirm('Deseja mesmo excluir esta questão? Isto apagará o histórico dela no perfil dos alunos.')" style="display:inline;">
                      <input type="hidden" name="acao" value="del_questao">
                      <input type="hidden" name="questao_id" value="<?php echo $q['id']; ?>">
                      <button type="submit" class="btn-deletar" style="padding: 6px 10px;" title="Excluir">🗑️</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="aba-materias" class="aba-painel card-admin">
      <h2 style="margin-top: 0; color: var(--texto-principal); font-weight: 800;">Adicionar Tópico à Grade</h2>
      <form action="admin.php" method="POST" style="margin-bottom: 40px;">
        <input type="hidden" name="acao" value="add_topico">
        <div class="form-row">
          <div class="form-group">
            <label>Frente do ENEM</label>
            <select name="frente_id" class="form-control" required>
              <?php foreach ($frentes as $f): ?>
                <option value="<?php echo $f['id']; ?>"><?php echo $f['icone'] . ' ' . htmlspecialchars($f['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Nome do Tópico</label>
            <input type="text" name="nome_topico" class="form-control" placeholder="Ex: Termoquímica" required>
          </div>
        </div>
        <button type="submit" class="btn-submit">➕ Criar Tópico</button>
      </form>

      <h3 style="color: var(--texto-principal); border-top: 1px solid var(--borda); padding-top: 25px;">Matérias Ativas</h3>
      <div style="overflow-x: auto;">
        <table class="tabela-gestao">
          <thead><tr><th>Frente</th><th>Tópico</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach ($topicos as $t): ?>
              <tr>
                <td><?php echo htmlspecialchars($t['nome_frente']); ?></td>
                <td><strong><?php echo htmlspecialchars($t['nome']); ?></strong></td>
                <td>
                  <form action="admin.php" method="POST" onsubmit="return confirm('Apagar este tópico excluirá todas as aulas e questões dele!')">
                    <input type="hidden" name="acao" value="del_topico">
                    <input type="hidden" name="topico_id" value="<?php echo $t['id']; ?>">
                    <button type="submit" class="btn-deletar">Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div id="aba-aulas" class="aba-painel card-admin">
      <h2 style="margin-top: 0; color: var(--texto-principal); font-weight: 800;">Adicionar Subtópico / Aula</h2>
      <form action="admin.php" method="POST">
        <input type="hidden" name="acao" value="add_aula">
        <div class="form-row">
          <div class="form-group">
            <label>Vincular a qual Tópico?</label>
            <select name="topico_id" class="form-control" required>
              <?php foreach ($topicos as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo "[".htmlspecialchars($t['nome_frente'])."] ".htmlspecialchars($t['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Título da Aula</label>
            <input type="text" name="titulo_aula" class="form-control" placeholder="Ex: Aula 2: Pilhas e Baterias" required>
          </div>
        </div>
        <div class="form-group">
          <label>Link do Vídeo (YouTube Embed)</label>
          <input type="url" name="video_url" class="form-control" placeholder="Ex: https://www.youtube.com/embed/XXXXX">
        </div>
        <div class="form-group">
          <label>Resumo Teórico</label>
          <textarea name="resumo_aula" class="form-control" rows="6" placeholder="Escreva o texto de apoio da aula..."></textarea>
        </div>
        <button type="submit" class="btn-submit">💾 Salvar Aula</button>
      </form>
    </div>

  </main>

  <script>
    const questoesDB = <?php echo json_encode($questoes_json_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  </script>

  <script>
    function alternarAba(evt, nomeAba) {
      document.querySelectorAll(".aba-painel").forEach(painel => painel.classList.remove("ativo"));
      document.querySelectorAll(".aba-link").forEach(btn => btn.classList.remove("ativa"));
      document.getElementById(nomeAba).classList.add("ativo");
      evt.currentTarget.classList.add("ativa");
    }

    function alternarDropdown(id) {
        document.querySelectorAll('.dropdown-conteudo').forEach(drop => {
            if(drop.id !== id) drop.classList.remove('mostrar');
        });
        document.getElementById(id).classList.toggle('mostrar');
    }
    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => drop.classList.remove('mostrar'));
        }
    }

    // A MÁGICA DA EDIÇÃO (Popula o form lá em cima com os dados da questão clicada)
    function ativarEdicaoQuestao(id) {
        const questao = questoesDB[id];
        if (!questao) return;

        // Alterar Cabeçalho e Ação do Form
        document.getElementById('tituloFormQuestao').innerHTML = "✏️ Editando a Questão #" + id;
        document.getElementById('inputAcao').value = "edit_questao";
        document.getElementById('inputQuestaoId').value = id;

        // Preencher Campos Base
        document.getElementById('selectTopico').value = questao.subtopic_id;
        document.getElementById('selectAula').value = questao.aula_id || "";
        document.getElementById('textoEnunciado').value = questao.enunciado;

        // Preencher Alternativas e descobrir qual é a correta
        let correta = "A";
        questao.alternativas.forEach(alt => {
            const letraLow = alt.letra.toLowerCase();
            const inputAlt = document.getElementById('alt_' + letraLow);
            if (inputAlt) { inputAlt.value = alt.texto_alternativa; }
            if (alt.eh_correta == 1) { correta = alt.letra; }
        });
        document.getElementById('selectCorreta').value = correta;

        // Alterar Botões
        document.getElementById('btnSalvarQuestao').innerHTML = "💾 Salvar Alterações";
        document.getElementById('btnCancelarEdicao').style.display = "block";

        // Scrollar suavemente até ao formulário
        document.getElementById('cardFormQuestao').scrollIntoView({ behavior: 'smooth' });
    }

    // Função para voltar o Form ao modo de "Adicionar"
    function cancelarEdicao() {
        document.getElementById('formQuestao').reset();
        document.getElementById('tituloFormQuestao').innerHTML = "➕ Inserir Nova Questão";
        document.getElementById('inputAcao').value = "add_questao";
        document.getElementById('inputQuestaoId').value = "";
        
        document.getElementById('btnSalvarQuestao').innerHTML = "🚀 Publicar Questão";
        document.getElementById('btnCancelarEdicao').style.display = "none";
    }
  </script>
</body>
</html>
