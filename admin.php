<?php
session_start();
require_once 'config.php';

// Proteção Avançada: Se o usuário não estiver na Whitelist de emails, é expulso para o dashboard
if (!verificarSeEhAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$mensagem = "";
$tipo_mensagem = "sucesso";

// Função simples para gerar Slugs a partir dos nomes (Ex: "Físico-Química" -> "fisico-quimica")
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
        // 1. ADICIONAR TÓPICO (MATÉRIA)
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

        // 3. ADICIONAR AULA (SUBTÓPICO SPECÍFICO)
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_aula') {
            $topico_id = $_POST['topico_id'];
            $titulo = trim($_POST['titulo_aula']);
            $video_url = trim($_POST['video_url']);
            $resumo = trim($_POST['resumo_aula']);

            if (!empty($titulo)) {
                $stmt = $pdo->prepare("INSERT INTO aulas (topico_id, titulo, video_url, resumo) VALUES (:tid, :titulo, :url, :resumo)");
                $stmt->execute([':tid' => $topico_id, ':titulo' => $titulo, ':url' => $video_url, ':resumo' => $resumo]);
                $mensagem = "Subtópico de aula <strong>$titulo</strong> integrado à grade!";
            }
        }

        // 4. CADASTRAR QUESTÃO + ALTERNATIVAS
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_questao') {
            $topico_id = $_POST['topico_id'];
            $aula_id = !empty($_POST['aula_id']) ? $_POST['aula_id'] : null;
            $enunciado = trim($_POST['enunciado']);
            $correta = $_POST['correta']; // Letra correspondente (A, B, C, D ou E)

            if (!empty($enunciado)) {
                // Inserir a questão
                $stmtQ = $pdo->prepare("INSERT INTO questions (subtopic_id, aula_id, statement) VALUES (:sid, :aid, :statement)");
                $stmtQ->execute([':sid' => $topico_id, ':aid' => $aula_id, ':statement' => $enunciado]);
                $question_id = $pdo->lastInsertId();

                // Arrumar o array das alternativas informadas
                $letras = ['A', 'B', 'C', 'D', 'E'];
                foreach ($letras as $letra) {
                    $texto_alt = trim($_POST['alt_' . strtolower($letra)]);
                    $is_correct = ($correta === $letra) ? 1 : 0;

                    $stmtA = $pdo->prepare("INSERT INTO alternatives (question_id, letter, text_content, is_correct) VALUES (:qid, :letter, :txt, :isc)");
                    $stmtA->execute([
                        ':qid' => $question_id,
                        ':letter' => $letra,
                        ':txt' => $texto_alt,
                        ':isc' => $is_correct
                    ]);
                }
                $mensagem = "Questão do ENEM cadastrada e vinculada com sucesso!";
            }
        }
    } catch (PDOException $e) {
        $mensagem = "Erro operacional no Banco de Dados: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// ==========================================
// PUXAR DADOS PARA EXIBIÇÃO NOS SELECTS
// ==========================================
$frentes = $pdo->query("SELECT * FROM frentes ORDER BY id ASC")->fetchAll();
$topicos = $pdo->query("SELECT t.*, f.nome as nome_frente FROM topicos t JOIN frentes f ON t.frente_id = f.id ORDER BY f.id, t.id ASC")->fetchAll();
$aulas   = $pdo->query("SELECT a.*, t.nome as nome_topico FROM aulas a JOIN topicos t ON a.topico_id = t.id ORDER BY t.id, a.id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel Administrativo | Atomicamente</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    .admin-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
    .card-admin { background: white; border-radius: 16px; border: 1px solid var(--borda); padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-top: 20px; }
    
    /* ABAS */
    .aba-nav { display: flex; gap: 10px; border-bottom: 2px solid var(--borda); margin-bottom: 25px; }
    .aba-link { padding: 12px 20px; background: none; border: none; font-family: 'Inter', sans-serif; font-size: 0.95rem; font-weight: 600; color: var(--cinza-texto); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; }
    .aba-link.ativa { color: var(--roxo-base); border-bottom: 2px solid var(--roxo-base); }
    .aba-painel { display: none; }
    .aba-painel.ativo { display: block; }

    /* FORMULÁRIOS POLIDOS */
    .form-group { margin-bottom: 18px; display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 0.9rem; font-weight: 600; color: var(--roxo-profundo); }
    .form-control { padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Inter', sans-serif; font-size: 0.95rem; transition: border 0.2s; }
    .form-control:focus { outline: none; border-color: var(--roxo-base); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .btn-admin { background: var(--roxo-base); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; font-family: 'Inter', sans-serif; transition: background 0.2s; }
    .btn-admin:hover { background: var(--roxo-vivo); }
    
    /* ALERTAS */
    .alerta { padding: 15px; border-radius: 8px; font-size: 0.95rem; margin-bottom: 20px; font-weight: 500; }
    .alerta.sucesso { background: #ecfdf5; border: 1px solid #10b981; color: #065f46; }
    .alerta.aviso { background: #fffbeb; border: 1px solid #f59e0b; color: #78350f; }
    .alerta.erro { background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; }

    /* TABELAS DE GESTÃO */
    .tabela-gestao { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .tabela-gestao th { text-align: left; padding: 12px; background: #f8fafc; border-bottom: 2px solid var(--borda); font-size: 0.85rem; text-transform: uppercase; color: #64748b; }
    .tabela-gestao td { padding: 12px; border-bottom: 1px solid var(--borda); font-size: 0.95rem; color: #334155; }
    .btn-deletar { background: none; border: none; color: #ef4444; font-weight: 600; cursor: pointer; font-size: 0.88rem; }
    .btn-deletar:hover { text-decoration: underline; }
  </style>
    <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 32px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem" style="background: #ef4444;">PAINEL ADMIN</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 20px;">
        <button id="btn-tema" onclick="alternarModoNoturno()" style="background: none; border: 1px solid var(--borda); color: var(--roxo-base); padding: 8px 12px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
          🌙 Modo Escuro
        </button>

        <a href="dashboard.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Painel Inicial</a>
        <a href="materias.php" style="color: var(--roxo-base); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Visão do Aluno →</a>
        <a href="logout.php" style="color: #ef4444; text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-left: 5px;">Sair</a>
      </div>
    </div>
  </header>

  <main class="admin-container">
    
    <div style="margin-bottom: 25px;">
      <h1 style="margin: 0; font-size: 1.8rem; color: var(--roxo-profundo); font-weight: 800;">Gestão da Estrutura Pedagógica</h1>
      <p style="margin: 5px 0 0 0; color: var(--cinza-texto); font-size: 0.95rem;">Controle de frentes, inclusão de subconteúdos específicos e alimentação do banco de questões.</p>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="alerta <?php echo $tipo_mensagem; ?>">
        <?php echo $mensagem; ?>
      </div>
    <?php endif; ?>

    <div class="aba-nav">
      <button class="aba-link ativa" onclick="alternarAba(event, 'aba-materias')">📂 Matérias (Tópicos)</button>
      <button class="aba-link" onclick="alternarAba(event, 'aba-aulas')">📖 Subtópicos & Aulas</button>
      <button class="aba-link" onclick="alternarAba(event, 'aba-questoes')">📝 Inserir Exercícios</button>
    </div>

    <div id="aba-materias" class="aba-painel ativo card-admin">
      <h3 style="margin-top: 0; color: var(--roxo-profundo);">Criar Nova Matéria na Grade</h3>
      <form action="admin.php" method="POST" style="margin-bottom: 30px;">
        <input type="hidden" name="acao" value="add_topico">
        <div class="form-row">
          <div class="form-group">
            <label>Selecione a Frente do ENEM</label>
            <select name="frente_id" class="form-control" required>
              <?php foreach ($frentes as $f): ?>
                <option value="<?php echo $f['id']; ?>"><?php echo $f['icone'] . ' ' . $f['nome']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Nome do Tópico/Matéria</label>
            <input type="text" name="nome_topico" class="form-control" placeholder="Ex: Eletroquímica" required>
          </div>
        </div>
        <button type="submit" class="btn-admin">Adicionar à Grade</button>
      </form>

      <h3 style="color: var(--roxo-profundo); border-top: 1px solid var(--borda); padding-top: 20px;">Matérias Ativas</h3>
      <table class="tabela-gestao">
        <thead>
          <tr>
            <th>Frente</th>
            <th>Nome do Tópico</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topicos as $t): ?>
            <tr>
              <td><strong><?php echo $t['nome_frente']; ?></strong></td>
              <td><?php echo $t['nome']; ?></td>
              <td>
                <form action="admin.php" method="POST" onsubmit="return confirm('Tem certeza? Isso apagará todas as aulas e questões vinculadas!')" style="display:inline;">
                  <input type="hidden" name="acao" value="del_topico">
                  <input type="hidden" name="topico_id" value="<?php echo $t['id']; ?>">
                  <button type="submit" class="btn-deletar">Remover</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div id="aba-aulas" class="aba-painel card-admin">
      <h3 style="margin-top: 0; color: var(--roxo-profundo);">Criar Subtópico / Especificação de Aula</h3>
      <form action="admin.php" method="POST">
        <input type="hidden" name="acao" value="add_aula">
        
        <div class="form-row">
          <div class="form-group">
            <label>Vincular à Matéria Base</label>
            <select name="topico_id" class="form-control" required>
              <?php foreach ($topicos as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo "[".$t['nome_frente']."] - ".$t['nome']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Título Específico do Subtópico</label>
            <input type="text" name="titulo_aula" class="form-control" placeholder="Ex: Aula 2: Balanceamento por Oxirredução" required>
          </div>
        </div>

        <div class="form-group">
          <label>Link do Vídeo Incorporado (Embed do YouTube)</label>
          <input type="url" name="video_url" class="form-control" placeholder="Ex: https://www.youtube.com/embed/XXXXX">
        </div>

        <div class="form-group">
          <label>Texto de Resumo / Teoria da Aula</label>
          <textarea name="resumo_aula" class="form-control" rows="4" placeholder="Escreva o resumo teórico em HTML ou texto limpo..."></textarea>
        </div>

        <button type="submit" class="btn-admin">Salvar Subtópico</button>
      </form>
    </div>

    <div id="aba-questoes" class="aba-painel card-admin">
      <h3 style="margin-top: 0; color: var(--roxo-profundo);">Alimentar Banco de Questões (ENEM/Fixação)</h3>
      <form action="admin.php" method="POST">
        <input type="hidden" name="acao" value="add_questao">

        <div class="form-row">
          <div class="form-group">
            <label>Matéria (Tópico)</label>
            <select name="topico_id" class="form-control" required>
              <?php foreach ($topicos as $t): ?>
                <option value="<?php echo $t['id']; ?>"><?php echo $t['nome']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Subtópico / Aula Específica (Opcional)</label>
            <select name="aula_id" class="form-control">
              <option value="">Apenas vincular ao Tópico Geral</option>
              <?php foreach ($aulas as $a): ?>
                <option value="<?php echo $a['id']; ?>"><?php echo "[".$a['nome_topico']."] - ".$a['titulo']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Enunciado Completo da Questão</label>
          <textarea name="enunciado" class="form-control" rows="5" placeholder="Insira o texto base e o comando da questão aqui..." required></textarea>
        </div>

        <h4 style="color: var(--roxo-profundo); margin: 25px 0 10px 0;">Texto das Alternativas</h4>
        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $letra): ?>
          <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px;">
            <span style="font-weight: 800; color: var(--roxo-base); min-width: 20px;"><?php echo $letra; ?>)</span>
            <input type="text" name="alt_<?php echo strtolower($letra); ?>" class="form-control" style="flex: 1;" placeholder="Texto da alternativa <?php echo $letra; ?>" required>
          </div>
        <?php endforeach; ?>

        <div class="form-group" style="margin-top: 20px;">
          <label style="color: var(--sucesso);">Qual é a Alternativa Correta?</label>
          <select name="correta" class="form-control" style="border-color: var(--sucesso); font-weight: 700; color: #065f46;" required>
            <option value="A">Alternativa A</option>
            <option value="B">Alternativa B</option>
            <option value="C">Alternativa C</option>
            <option value="D">Alternativa D</option>
            <option value="E">Alternativa E</option>
          </select>
        </div>

        <button type="submit" class="btn-admin" style="width: 100%; margin-top: 15px;">Publicar Questão Oficialmente</button>
      </form>
    </div>

  </main>

  <script>
    function alternarAba(evt, nomeAba) {
      // Ocultar todos os painéis
      const paineis = document.getElementsByClassName("aba-painel");
      for (let i = 0; i < paineis.length; i++) {
        paineis[i].classList.remove("ativo");
      }
      // Desativar estilo de todos os botões
      const botoes = document.getElementsByClassName("aba-link");
      for (let i = 0; i < botoes.length; i++) {
        botoes[i].classList.remove("ativa");
      }
      // Mostrar painel selecionado e ativar botão
      document.getElementById(nomeAba).classList.add("ativo");
      evt.currentTarget.classList.add("ativa");
    }
  </script>
</body>
</html>
