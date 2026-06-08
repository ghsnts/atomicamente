<?php
session_start();
require_once 'config.php';

// Proteção Avançada de Acesso
if (!function_exists('verificarSeEhAdmin')) {
    function verificarSeEhAdmin() { 
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'; 
    }
}

if (!verificarSeEhAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$primeiro_nome = explode(' ', trim($_SESSION['user_nome'] ?? 'Admin'))[0];

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
                $mensagem = "Matéria acadêmica <strong>$nome_topico</strong> integrada com sucesso!";
            }
        }

        // 2. EXCLUIR TÓPICO
        if (isset($_POST['acao']) && $_POST['acao'] === 'del_topico') {
            $stmt = $pdo->prepare("DELETE FROM topicos WHERE id = :id");
            $stmt->execute([':id' => $_POST['topico_id']]);
            $mensagem = "Módulo temático e suas ramificações foram removidos do ecossistema.";
            $tipo_mensagem = "aviso";
        }

        // 3. AULAS: ADICIONAR, EDITAR, EXCLUIR
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_aula') {
            $topico_id = $_POST['topico_id'];
            $titulo = trim($_POST['titulo_aula']);
            $video_url = trim($_POST['video_url']);
            $resumo = trim($_POST['resumo_aula']);

            if (!empty($titulo)) {
                $stmt = $pdo->prepare("INSERT INTO aulas (topico_id, titulo, video_url, resumo) VALUES (:tid, :titulo, :url, :resumo)");
                $stmt->execute([':tid' => $topico_id, ':titulo' => $titulo, ':url' => $video_url, ':resumo' => $resumo]);
                $mensagem = "Nova aula injetada na malha de conteúdo ativo!";
            }
        }
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'edit_aula') {
            $aula_id = $_POST['aula_id'];
            $topico_id = $_POST['topico_id'];
            $titulo = trim($_POST['titulo_aula']);
            $video_url = trim($_POST['video_url']);
            $resumo = trim($_POST['resumo_aula']);

            if (!empty($titulo)) {
                $stmt = $pdo->prepare("UPDATE aulas SET topico_id = ?, titulo = ?, video_url = ?, resumo = ? WHERE id = ?");
                $stmt->execute([$topico_id, $titulo, $video_url, $resumo, $aula_id]);
                $mensagem = "Aula atualizada com sucesso!";
            }
        }
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'del_aula') {
            $pdo->prepare("DELETE FROM aulas WHERE id = ?")->execute([$_POST['aula_id']]);
            $mensagem = "Aula excluída permanentemente."; 
            $tipo_mensagem = "aviso";
        }

        // 4. QUESTÕES: CADASTRAR, EDITAR, EXCLUIR
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_questao') {
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
            $topico_id = $_POST['topico_id'];
            $aula_id = !empty($_POST['aula_id']) ? $_POST['aula_id'] : null;
            $enunciado = trim($_POST['enunciado']);
            $correta = $_POST['correta']; 

            if (!empty($enunciado)) {
                $stmtQ = $pdo->prepare("INSERT INTO questions (subtopic_id, aula_id, enunciado, explicacao) VALUES (:sid, :aid, :enunciado, '')");
                $stmtQ->execute([':sid' => $topico_id, ':aid' => $aula_id, ':enunciado' => $enunciado]);
                $question_id = $pdo->lastInsertId();

                $letras = ['A', 'B', 'C', 'D', 'E'];
                foreach ($letras as $letra) {
                    $texto_alt = trim($_POST['alt_' . strtolower($letra)]);
                    $eh_correta = ($correta === $letra) ? 1 : 0;
                    $stmtA = $pdo->prepare("INSERT INTO alternatives (question_id, letra, texto_alternativa, eh_correta) VALUES (:qid, :letra, :txt, :isc)");
                    $stmtA->execute([':qid' => $question_id, ':letra' => $letra, ':txt' => $texto_alt, ':isc' => $eh_correta]);
                }
                $mensagem = "Questão homologada e adicionada ao banco avaliativo!";
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        }

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
                $mensagem = "Mutações na Questão #$questao_id salvas com sucesso!";
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
        }

        if (isset($_POST['acao']) && $_POST['acao'] === 'del_questao') {
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = :id");
            $stmt->execute([':id' => $_POST['questao_id']]);
            $mensagem = "Questão expurgada permanentemente do ecossistema.";
            $tipo_mensagem = "aviso";
        }

        // 5. ANEXOS: MATERIAL DE APOIO E FONTES
        if (isset($_POST['acao']) && $_POST['acao'] === 'add_material') {
            $aula_id = $_POST['aula_id'];
            $tipo = $_POST['tipo'];
            $nome = trim($_POST['nome']);
            $url = trim($_POST['url']);
            if (!empty($nome) && !empty($url)) {
                $stmt = $pdo->prepare("INSERT INTO materiais_apoio (aula_id, tipo, nome, url) VALUES (?, ?, ?, ?)");
                $stmt->execute([$aula_id, $tipo, $nome, $url]);
                $mensagem = "Documentação complementar acoplada à aula selecionada!";
            }
        }

        if (isset($_POST['acao']) && $_POST['acao'] === 'del_material') {
            $pdo->prepare("DELETE FROM materiais_apoio WHERE id = ?")->execute([$_POST['id']]);
            $mensagem = "Material de apoio desconectado."; 
            $tipo_mensagem = "aviso";
        }

        if (isset($_POST['acao']) && $_POST['acao'] === 'add_fonte') {
            $aula_id = $_POST['aula_id'];
            $descricao = trim($_POST['descricao']);
            $link = trim($_POST['link']);
            if (!empty($descricao)) {
                $stmt = $pdo->prepare("INSERT INTO fontes_aula (aula_id, descricao, link) VALUES (?, ?, ?)");
                $stmt->execute([$aula_id, $descricao, $link]);
                $mensagem = "Referência científica e metodológica indexada!";
            }
        }

        if (isset($_POST['acao']) && $_POST['acao'] === 'del_fonte') {
            $pdo->prepare("DELETE FROM fontes_aula WHERE id = ?")->execute([$_POST['id']]);
            $mensagem = "Indexador bibliográfico removido."; 
            $tipo_mensagem = "aviso";
        }

    } catch (PDOException $e) {
        $mensagem = "Falha crítica na camada de dados: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// ==========================================
// CAPTURA DE DATASETS OPERACIONAIS
// ==========================================
$frentes = $pdo->query("SELECT * FROM frentes ORDER BY id ASC")->fetchAll();
$topicos = $pdo->query("SELECT t.*, f.nome as nome_frente FROM topicos t JOIN frentes f ON t.frente_id = f.id ORDER BY f.id, t.id ASC")->fetchAll();
$aulas   = $pdo->query("SELECT a.*, t.nome as nome_topico FROM aulas a JOIN topicos t ON a.topico_id = t.id ORDER BY t.id, a.id ASC")->fetchAll();

// Prepara JS array das Aulas para Edição Dinâmica
$aulas_json_data = [];
foreach ($aulas as $a) {
    $aulas_json_data[$a['id']] = $a;
}

$lista_materiais = [];
$lista_fontes = [];
try {
    $lista_materiais = $pdo->query("SELECT m.*, a.titulo as nome_aula FROM materiais_apoio m JOIN aulas a ON m.aula_id = a.id ORDER BY m.id DESC")->fetchAll();
    $lista_fontes = $pdo->query("SELECT f.*, a.titulo as nome_aula FROM fontes_aula f JOIN aulas a ON f.aula_id = a.id ORDER BY f.id DESC")->fetchAll();
} catch (Exception $e) {}

$lista_questoes = $pdo->query("
    SELECT q.*, t.nome as nome_topico, a.titulo as nome_aula
    FROM questions q 
    JOIN topicos t ON q.subtopic_id = t.id 
    LEFT JOIN aulas a ON q.aula_id = a.id
    ORDER BY q.id DESC LIMIT 200
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    /* ================= PALETA DE CORES (EDTECH) ================= */
    :root {
        --bg-global: #f8fafc;
        --bg-card: #ffffff;
        --texto-principal: #1e293b;
        --texto-secundario: #64748b;
        --borda: #e2e8f0;
        --roxo-base: #7c3aed;
        --roxo-claro: #f5f3ff;
        --verde-sucesso: #10b981;
        --vermelho-erro: #ef4444;
        --amarelo-aviso: #f59e0b;
        --header-bg: #ffffff;
    }

    body.dark-mode {
        --bg-global: #0f172a;
        --bg-card: #1e293b;
        --texto-principal: #f8fafc;
        --texto-secundario: #94a3b8;
        --borda: #334155;
        --roxo-base: #8b5cf6;
        --roxo-claro: rgba(139, 92, 246, 0.15);
        --header-bg: #1e293b;
    }

    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); margin: 0; transition: background-color 0.3s, color 0.3s; }
    
    /* ================= HEADER (PLATAFORMA/PERFIL) ================= */
    .topo-dash { 
        border-bottom: 1px solid var(--borda); 
        background: var(--header-bg); 
        position: sticky; 
        top: 0; 
        z-index: 1000; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        transition: all 0.3s;
    }
    .nav-dash { 
        padding: 12px 20px; 
        max-width: 1300px; 
        margin: 0 auto; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        width: 100%; 
        box-sizing: border-box; 
    }
    .marca-dash { 
        font-weight: 800; 
        font-size: 1.25rem; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        text-decoration: none; 
        color: var(--texto-principal); 
        letter-spacing: -0.03em; 
    }
    .badge-enem { font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; color: white; background: var(--vermelho-erro); letter-spacing: 0.05em; }

    /* DROPDOWNS E CONFIGURAÇÕES */
    .menu-dropdown { position: relative; display: inline-block; }
    .dropdown-conteudo { display: none; position: absolute; right: 0; top: 40px; background-color: var(--bg-card); min-width: 200px; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1); border: 1px solid var(--borda); border-radius: 12px; z-index: 1001; overflow: hidden; }
    .dropdown-conteudo.mostrar { display: block; }
    .dropdown-item { color: var(--texto-principal); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.9rem; font-weight: 500; transition: background 0.2s; cursor: pointer; }
    .dropdown-item:hover { background-color: var(--bg-global); color: var(--roxo-base); }
    .dropdown-divisor { border-top: 1px solid var(--borda); margin: 4px 0; }

    /* ================= MAIN CONTAINER ================= */
    .admin-container { max-width: 1300px; margin: 40px auto; padding: 0 24px; box-sizing: border-box;}
    
    .hero-admin { 
        background: linear-gradient(135deg, var(--roxo-base), #4f46e5);
        border-radius: 24px; 
        padding: 40px; 
        color: white; 
        margin-bottom: 40px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        flex-wrap: wrap; 
        gap: 24px; 
        box-shadow: 0 10px 30px rgba(124, 58, 237, 0.3);
    }
    .hero-admin h1 { margin: 0 0 8px 0; font-size: 2.4rem; font-weight: 800; letter-spacing: -0.04em; }
    .hero-admin p { margin: 0; font-size: 1.1rem; opacity: 0.9; max-width: 550px; line-height: 1.6; }
    
    .stats-admin { display: flex; gap: 16px; flex-wrap: wrap; }
    .stat-box { 
        background: rgba(255, 255, 255, 0.15); 
        border: 1px solid rgba(255, 255, 255, 0.2); 
        padding: 20px 32px; 
        border-radius: 16px; 
        text-align: center; 
        min-width: 100px;
        backdrop-filter: blur(5px);
    }
    .stat-num { font-size: 2.4rem; font-weight: 800; line-height: 1; margin-bottom: 6px; }
    .stat-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 700; opacity: 0.9; }

    .card-admin { 
        background: var(--bg-card); 
        border-radius: 24px; 
        border: 1px solid var(--borda); 
        padding: 32px; 
        margin-top: 24px; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
    }
    
    /* ================= ABAS ================= */
    .aba-nav { 
        display: flex; 
        gap: 8px; 
        border-bottom: 2px solid var(--borda); 
        margin-bottom: 32px; 
        padding-bottom: 16px; 
        overflow-x: auto; 
    }
    .aba-link { 
        padding: 12px 24px; 
        background: var(--bg-global); 
        border: 1px solid var(--borda); 
        border-radius: 12px; 
        font-weight: 700; 
        font-size: 0.95rem; 
        color: var(--texto-secundario); 
        cursor: pointer; 
        transition: all 0.2s; 
        white-space: nowrap; 
    }
    .aba-link:hover { color: var(--roxo-base); border-color: var(--roxo-base); }
    .aba-link.ativa { 
        background: var(--roxo-base); 
        color: white; 
        border-color: var(--roxo-base); 
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
    }
    
    .aba-painel { display: none; }
    .aba-painel.ativo { display: block; animation: smoothShift 0.35s ease-out; }
    @keyframes smoothShift { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

    /* ================= FORMULÁRIOS ================= */
    .form-group { margin-bottom: 24px; display: flex; flex-direction: column; gap: 8px; }
    .form-group label { font-size: 0.95rem; font-weight: 700; color: var(--texto-principal); }
    
    .form-control { 
        padding: 14px 18px; 
        border: 2px solid var(--borda); 
        border-radius: 12px; 
        background: var(--bg-global); 
        color: var(--texto-principal); 
        font-family: 'Inter', sans-serif; 
        font-size: 1rem; 
        transition: all 0.2s; 
    }
    .form-control:focus { outline: none; border-color: var(--roxo-base); box-shadow: 0 0 0 4px var(--roxo-claro); }
    .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; }
    
    .btn-submit { 
        background: linear-gradient(135deg, var(--roxo-base), #4f46e5); 
        color: white; 
        border: none; 
        padding: 16px 32px; 
        border-radius: 12px; 
        font-weight: 800; 
        font-size: 1.05rem; 
        cursor: pointer; 
        transition: all 0.2s; 
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3); 
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(79, 70, 229, 0.4); }
    .btn-cancelar { 
        background: var(--bg-global); 
        color: var(--texto-secundario); 
        border: 2px solid var(--borda); 
        padding: 16px 32px; 
        border-radius: 12px; 
        font-weight: 800; 
        font-size: 1.05rem; 
        cursor: pointer; 
        display: none; 
        transition: all 0.2s;
    }
    .btn-cancelar:hover { color: var(--texto-principal); border-color: var(--texto-principal); }
    
    .alerta { padding: 18px 24px; border-radius: 16px; font-size: 0.95rem; margin-bottom: 32px; font-weight: 600; display: flex; align-items: center; gap: 12px; }
    .alerta.sucesso { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--verde-sucesso); }
    .alerta.aviso { background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); color: var(--amarelo-aviso); }
    .alerta.erro { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--vermelho-erro); }

    .opcao-admin { 
        display: flex; align-items: center; gap: 16px; padding: 14px 20px; 
        border: 2px solid var(--borda); border-radius: 12px; 
        background-color: var(--bg-global); margin-bottom: 14px; transition: border-color 0.2s;
    }
    .opcao-admin:focus-within { border-color: var(--roxo-base); }
    .opcao-admin .letra { font-weight: 800; font-size: 1.1rem; color: var(--roxo-base); width: 24px; }
    .opcao-admin input[type="text"] { flex: 1; border: none; background: transparent; font-size: 1rem; color: var(--texto-principal); outline: none; }

    /* ================= TABELAS & LISTAS ================= */
    .tabela-container { overflow-x: auto; border-radius: 16px; border: 1px solid var(--borda); background: var(--bg-card); }
    .tabela-gestao { width: 100%; border-collapse: collapse; text-align: left; }
    .tabela-gestao th { padding: 18px 24px; background: var(--bg-global); border-bottom: 2px solid var(--borda); font-size: 0.8rem; text-transform: uppercase; color: var(--texto-secundario); font-weight: 800; letter-spacing: 0.05em; }
    .tabela-gestao td { padding: 18px 24px; border-bottom: 1px solid var(--borda); font-size: 0.95rem; color: var(--texto-principal); font-weight: 500;}
    .tabela-gestao tr:last-child td { border-bottom: none; }
    .tabela-gestao tr:hover td { background: var(--bg-global); }
    
    .btn-deletar { background: rgba(239, 68, 68, 0.1); color: var(--vermelho-erro); font-weight: 700; padding: 8px 14px; border-radius: 10px; cursor: pointer; border: 1px solid rgba(239, 68, 68, 0.2); transition: all 0.2s; display: inline-flex; align-items: center; }
    .btn-deletar:hover { background: var(--vermelho-erro); color: white; }
    .btn-editar { background: rgba(124, 58, 237, 0.1); color: var(--roxo-base); font-weight: 700; padding: 8px 14px; border-radius: 10px; cursor: pointer; border: 1px solid rgba(124, 58, 237, 0.2); transition: all 0.2s; margin-right: 6px; }
    .btn-editar:hover { background: var(--roxo-base); color: white; }

    .badge-conteudo { display: inline-block; padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-right: 5px; }
    .badge-video { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
    .badge-texto { background: rgba(16, 185, 129, 0.1); color: #059669; }

    .filtros-container { display: flex; gap: 16px; margin-bottom: 24px; background: var(--bg-global); padding: 16px; border-radius: 16px; border: 1px solid var(--borda); align-items: center; }
    .filtros-container select { flex: 1; max-width: 320px; }
    .search-input { flex: 1; background: var(--bg-card); border: 2px solid var(--borda); padding: 14px 16px; border-radius: 12px; color: var(--texto-principal); font-size: 0.95rem; font-family: 'Inter'; outline: none;}
    .search-input:focus { border-color: var(--roxo-base); }

    .grid-duplo { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 32px; }
    .listagem-cards { display: flex; flex-direction: column; gap: 14px; max-height: 520px; overflow-y: auto; padding-right: 8px; }
    .item-card { background: var(--bg-global); border: 1px solid var(--borda); padding: 18px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; transition: border-color 0.2s; }
    .item-card:hover { border-color: var(--roxo-base); }
  </style>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 30px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem">CENTRO DE COMANDO</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        <a href="materias.php" style="color: var(--texto-secundario); text-decoration: none; font-weight: 700; font-size: 0.9rem;">📚 Ver Sala de Aula</a>
          
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 6px 10px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; cursor: pointer;">🛠️</button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()">🌙 Alternar Tema</div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 6px 12px; font-size: 0.85rem; border-radius: 8px; font-weight: 700; cursor: pointer;">👤 <?php echo $primeiro_nome; ?> ▼</button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <a href="dashboard.php" class="dropdown-item">📈 Meu Painel</a>
            <a href="logout.php" class="dropdown-item" style="color: var(--vermelho-erro);">🚪 Sair do Sistema</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="admin-container">
    
    <div class="hero-admin">
      <div>
        <h1>Gestão da Estrutura Pedagógica</h1>
        <p>Alimente a grade curricular estruturada para o ENEM, vincule mídias dinâmicas e monitore o repositório avaliativo integrado.</p>
      </div>
      <div class="stats-admin">
        <div class="stat-box">
          <div class="stat-num"><?php echo $total_topicos; ?></div><div class="stat-label">Módulos</div>
        </div>
        <div class="stat-box">
          <div class="stat-num"><?php echo $total_aulas; ?></div><div class="stat-label">Aulas</div>
        </div>
        <div class="stat-box">
          <div class="stat-num"><?php echo $total_questoes; ?></div><div class="stat-label">Questões</div>
        </div>
      </div>
    </div>

    <?php if (!empty($mensagem)): ?>
      <div class="alerta <?php echo $tipo_mensagem; ?>">
        <span><?php echo $tipo_mensagem === 'sucesso' ? '✨' : '⚡'; ?></span>
        <div><?php echo $mensagem; ?></div>
      </div>
    <?php endif; ?>

    <div class="aba-nav">
      <button class="aba-link ativa" onclick="alternarAba(event, 'aba-aulas')">📖 Matriz de Aulas</button>
      <button class="aba-link" onclick="alternarAba(event, 'aba-questoes')">📝 Repositório de Questões</button>
      <button class="aba-link" onclick="alternarAba(event, 'aba-anexos')">📎 Acervo Digital (Anexos)</button>
      <button class="aba-link" onclick="alternarAba(event, 'aba-materias')">📂 Tópicos & Frentes</button>
    </div>

    <div id="aba-aulas" class="aba-painel ativo">
      <div class="card-admin" id="cardFormAula" style="margin-top: 0;">
        <h2 id="tituloFormAula" style="margin-top: 0; font-weight: 800; font-size: 1.5rem;">➕ Cadastrar Nova Aula</h2>
        <form action="admin.php" method="POST" id="formAula">
          <input type="hidden" name="acao" id="aulaAcao" value="add_aula">
          <input type="hidden" name="aula_id" id="aulaId" value="">

          <div class="form-row">
            <div class="form-group">
              <label>Pertence a qual Tópico (Módulo)?</label>
              <select name="topico_id" id="aulaTopico" class="form-control" required>
                <option value="">Selecione um tópico...</option>
                <?php foreach ($topicos as $t): ?>
                  <option value="<?php echo $t['id']; ?>"><?php echo "[".htmlspecialchars($t['nome_frente'])."] ".htmlspecialchars($t['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Nome da Aula</label>
              <input type="text" name="titulo_aula" id="aulaTitulo" class="form-control" placeholder="Ex: Ligações Covalentes" required>
            </div>
          </div>

          <div class="form-group">
            <label>🎥 Link da Videoaula (YouTube Embed Link - Opcional)</label>
            <input type="url" name="video_url" id="aulaVideo" class="form-control" placeholder="https://www.youtube.com/embed/...">
          </div>

          <div class="form-group">
            <label>📝 Aula em Texto / Resumo Teórico Direcionado</label>
            <textarea name="resumo_aula" id="aulaTexto" class="form-control" rows="6" placeholder="Escreva a aula aqui. Você pode colar o texto completo ou um resumo direto ao ponto..."></textarea>
          </div>

          <div style="display:flex; gap:16px; margin-top: 20px;">
            <button type="submit" class="btn-submit" id="btnSalvarAula">Salvar Aula</button>
            <button type="button" class="btn-cancelar" id="btnCancelarAula" onclick="cancelarEdicaoAula()">Cancelar Edição</button>
          </div>
        </form>
      </div>

      <div class="card-admin">
        <h2 style="margin-top: 0; font-weight: 800; font-size: 1.4rem;">Aulas Existentes</h2>
        <div class="tabela-container">
          <table class="tabela-gestao">
            <thead>
              <tr>
                <th>Tópico Base</th>
                <th>Nome da Aula</th>
                <th>Conteúdo</th>
                <th style="text-align: right;">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($aulas as $a): ?>
                <tr>
                  <td><span style="font-size: 0.85rem; color: var(--texto-secundario); font-weight: 700;"><?php echo htmlspecialchars($a['nome_topico']); ?></span></td>
                  <td style="font-weight: 600;"><?php echo htmlspecialchars($a['titulo']); ?></td>
                  <td>
                    <?php if(!empty($a['video_url'])): ?><span class="badge-conteudo badge-video">▶ Vídeo</span><?php endif; ?>
                    <?php if(!empty($a['resumo'])): ?><span class="badge-conteudo badge-texto">📄 Texto</span><?php endif; ?>
                  </td>
                  <td style="text-align: right; white-space: nowrap;">
                    <button class="btn-editar" onclick="ativarEdicaoAula(<?php echo $a['id']; ?>)">✏️</button>
                    <form action="admin.php" method="POST" style="display:inline;" onsubmit="return confirm('Apagar esta aula vai afetar o andamento dos alunos. Tem certeza?')">
                      <input type="hidden" name="acao" value="del_aula">
                      <input type="hidden" name="aula_id" value="<?php echo $a['id']; ?>">
                      <button type="submit" class="btn-deletar">🗑️</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="aba-questoes" class="aba-painel">
      <div class="card-admin" id="cardFormQuestao" style="margin-top: 0;">
        <h2 id="tituloFormQuestao" style="margin-top: 0; font-weight: 800; font-size: 1.5rem;">➕ Inserir Nova Questão</h2>
        
        <form action="admin.php" method="POST" id="formQuestao">
          <input type="hidden" name="acao" id="inputAcao" value="add_questao">
          <input type="hidden" name="questao_id" id="inputQuestaoId" value="">

          <div class="form-row">
            <div class="form-group">
              <label>Módulo Temático Relacionado</label>
              <select name="topico_id" id="selectTopico" class="form-control" required>
                <?php foreach ($topicos as $t): ?>
                  <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nome']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Subtópico / Fixação de Aula (Opcional)</label>
              <select name="aula_id" id="selectAula" class="form-control">
                <option value="">Ancorar estritamente ao Tópico Geral</option>
                <?php foreach ($aulas as $a): ?>
                  <option value="<?php echo $a['id']; ?>"><?php echo "[".htmlspecialchars($a['nome_topico'])."] - ".htmlspecialchars($a['titulo']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Enunciado (Suporta HTML)</label>
            <textarea name="enunciado" id="textoEnunciado" class="form-control" rows="6" required placeholder="Digite o comando ou contexto da questão..."></textarea>
          </div>

          <h3 style="font-weight: 800; font-size: 1.1rem; margin: 32px 0 16px 0; color: var(--texto-secundario);">Configuração de Alternativas</h3>
          <?php foreach (['A', 'B', 'C', 'D', 'E'] as $letra): ?>
            <div class="opcao-admin">
              <span class="letra"><?php echo $letra; ?>)</span>
              <input type="text" name="alt_<?php echo strtolower($letra); ?>" id="alt_<?php echo strtolower($letra); ?>" placeholder="Texto descritivo..." required>
            </div>
          <?php endforeach; ?>

          <div class="form-group" style="margin-top: 24px;">
            <label style="color: var(--verde-sucesso);">Gabarito Oficial Correto</label>
            <select name="correta" id="selectCorreta" class="form-control" style="border-color: rgba(16, 185, 129, 0.4); font-weight: 800; color: var(--verde-sucesso);" required>
              <option value="A">Alternativa A</option><option value="B">Alternativa B</option><option value="C">Alternativa C</option><option value="D">Alternativa D</option><option value="E">Alternativa E</option>
            </select>
          </div>

          <div style="display:flex; gap:16px; margin-top: 32px;">
            <button type="submit" class="btn-submit" id="btnSalvarQuestao">Publicar Questão</button>
            <button type="button" class="btn-cancelar" id="btnCancelarEdicaoQuestao" onclick="cancelarEdicaoQuestao()">Descartar Mudanças</button>
          </div>
        </form>
      </div>

      <div class="card-admin">
        <h2 style="margin-top: 0; font-weight: 800; font-size: 1.4rem;">Questões Catalogadas (Últimas 200)</h2>
        
        <div class="filtros-container">
            <select id="filtroTopico" class="form-control" onchange="filtrarQuestoes()">
                <option value="todos">Filtrar por Tópico (Todos)</option>
                <?php foreach ($topicos as $t): ?>
                    <option value="<?php echo htmlspecialchars($t['nome']); ?>"><?php echo htmlspecialchars($t['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="buscaQuestao" class="search-input" placeholder="Buscar por fragmento do enunciado..." oninput="filtrarQuestoes()">
        </div>

        <div class="tabela-container">
          <table class="tabela-gestao" id="tabelaQuestoes">
            <thead>
              <tr><th style="width: 80px;">ID</th><th style="width: 250px;">Módulo</th><th>Enunciado Parcial</th><th style="text-align: right; width: 140px;">Ações</th></tr>
            </thead>
            <tbody>
              <?php foreach ($lista_questoes as $q): ?>
                <tr class="questao-row" data-topico="<?php echo htmlspecialchars($q['nome_topico']); ?>">
                  <td><span style="color: var(--texto-secundario); font-weight: 800;">#<?php echo $q['id']; ?></span></td>
                  <td><span style="background: rgba(124, 58, 237, 0.1); border: 1px solid rgba(124, 58, 237, 0.2); padding: 4px 10px; border-radius: 8px; font-weight: 700; color: var(--roxo-base); font-size: 0.8rem;"><?php echo htmlspecialchars($q['nome_topico']); ?></span></td>
                  <td class="enunciado-alvo" style="font-weight: 500; color: var(--texto-secundario);"><?php echo mb_strimwidth(htmlspecialchars(strip_tags($q['enunciado'])), 0, 95, '...'); ?></td>
                  <td style="text-align: right; white-space: nowrap;">
                    <button type="button" class="btn-editar" onclick="ativarEdicaoQuestao(<?php echo $q['id']; ?>)" title="Editar">✏️</button>
                    <form action="admin.php" method="POST" style="display:inline;" onsubmit="return confirm('Deseja deletar permanentemente esta questão do banco?')">
                      <input type="hidden" name="acao" value="del_questao"><input type="hidden" name="questao_id" value="<?php echo $q['id']; ?>">
                      <button type="submit" class="btn-deletar" title="Excluir">🗑️</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="aba-anexos" class="aba-painel">
        <div class="grid-duplo">
            <div class="card-admin" style="margin-top: 0;">
                <h2 style="margin-top: 0; font-weight: 800; font-size: 1.3rem; color: var(--verde-sucesso);">📎 Acoplar Material de Apoio</h2>
                <form action="admin.php" method="POST" style="margin-bottom: 24px;">
                    <input type="hidden" name="acao" value="add_material">
                    <div class="form-group">
                        <label>Aula Destino</label>
                        <select name="aula_id" class="form-control" required>
                            <?php foreach ($aulas as $a): ?>
                                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row" style="grid-template-columns: 1fr;">
                      <div class="form-group">
                          <label>Natureza do Anexo</label>
                          <select name="tipo" class="form-control">
                              <option value="pdf">Documentação Técnica (PDF)</option>
                              <option value="link">Direcionamento Externo (Link)</option>
                          </select>
                      </div>
                    </div>
                    <div class="form-group">
                        <label>Nome do Arquivo / Call to Action</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: Mapa Mental - Estequiometria" required>
                    </div>
                    <div class="form-group">
                        <label>URL (Armazenamento Cloud ou Link)</label>
                        <input type="url" name="url" class="form-control" placeholder="https://drive.google.com/..." required>
                    </div>
                    <button type="submit" class="btn-submit" style="background: var(--verde-sucesso); box-shadow: none; width:100%;">Ancorar Arquivo</button>
                </form>

                <h3 style="margin-top: 32px; font-size: 1rem; color: var(--texto-secundario); border-bottom: 1px solid var(--borda); padding-bottom: 12px;">Materiais Disponibilizados</h3>
                <div class="listagem-cards">
                    <?php if(empty($lista_materiais)) echo "<p style='font-size:0.9rem; color:var(--texto-secundario);'>Nenhum material associado.</p>"; ?>
                    <?php foreach($lista_materiais as $m): ?>
                        <div class="item-card">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem; color: var(--texto-principal);"><?php echo $m['tipo'] == 'pdf' ? '📕' : '🔗'; ?> <?php echo htmlspecialchars($m['nome']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--texto-secundario); margin-top: 4px;">Vinculado à aula: <?php echo htmlspecialchars($m['nome_aula']); ?></div>
                            </div>
                            <form action="admin.php" method="POST">
                                <input type="hidden" name="acao" value="del_material"><input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                <button class="btn-deletar" style="padding: 6px 10px;">🗑️</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card-admin" style="margin-top: 0;">
                <h2 style="margin-top: 0; font-weight: 800; font-size: 1.3rem; color: var(--amarelo-aviso);">📖 Fontes Bibliográficas</h2>
                <form action="admin.php" method="POST" style="margin-bottom: 24px;">
                    <input type="hidden" name="acao" value="add_fonte">
                    <div class="form-group">
                        <label>Aula Destino</label>
                        <select name="aula_id" class="form-control" required>
                            <?php foreach ($aulas as $a): ?>
                                <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Credenciais (Autor, Obra, Localizadores, Edição)</label>
                        <input type="text" name="descricao" class="form-control" placeholder="Ex: Feltre, Ricardo. Química Geral Vol 1" required>
                    </div>
                    <div class="form-group">
                        <label>Link de Validação (Opcional)</label>
                        <input type="url" name="link" class="form-control" placeholder="https://...">
                    </div>
                    <button type="submit" class="btn-submit" style="background: var(--amarelo-aviso); box-shadow: none; color:#1e293b; width:100%;">Registrar Indexador</button>
                </form>

                <h3 style="margin-top: 32px; font-size: 1rem; color: var(--texto-secundario); border-bottom: 1px solid var(--borda); padding-bottom: 12px;">Fontes Indexadas</h3>
                <div class="listagem-cards">
                    <?php if(empty($lista_fontes)) echo "<p style='font-size:0.9rem; color:var(--texto-secundario);'>Nenhuma fonte referenciada.</p>"; ?>
                    <?php foreach($lista_fontes as $f): ?>
                        <div class="item-card">
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem; color: var(--texto-principal);">📚 <?php echo htmlspecialchars($f['descricao']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--texto-secundario); margin-top: 4px;">Aula: <?php echo htmlspecialchars($f['nome_aula']); ?></div>
                            </div>
                            <form action="admin.php" method="POST">
                                <input type="hidden" name="acao" value="del_fonte"><input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                <button class="btn-deletar" style="padding: 6px 10px;">🗑️</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="aba-materias" class="aba-painel card-admin">
      <h2 style="margin-top: 0; font-weight: 800; font-size: 1.5rem;">Engenharia de Módulos Temáticos</h2>
      <form action="admin.php" method="POST" style="margin-bottom: 40px;">
        <input type="hidden" name="acao" value="add_topico">
        <div class="form-row">
          <div class="form-group">
            <label>Frente Pedagógica Orientada</label>
            <select name="frente_id" class="form-control" required>
              <?php foreach ($frentes as $f): ?>
                <option value="<?php echo $f['id']; ?>"><?php echo $f['icone'] . ' ' . htmlspecialchars($f['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Designação Técnica do Tópico</label>
            <input type="text" name="nome_topico" class="form-control" placeholder="Ex: Termoquímica Avançada" required>
          </div>
        </div>
        <button type="submit" class="btn-submit">Criar Nova Categoria</button>
      </form>

      <h3 style="border-top: 1px solid var(--borda); padding-top: 32px; margin-bottom: 20px; font-size: 1.2rem;">Divisões em Execução</h3>
      <div class="tabela-container">
        <table class="tabela-gestao">
          <thead><tr><th>Frente Pertencente</th><th>Identificação Temática</th><th style="text-align: right; width: 150px;">Ações Críticas</th></tr></thead>
          <tbody>
            <?php foreach ($topicos as $t): ?>
              <tr>
                <td><span style="color: var(--texto-secundario); font-weight: 700;"><?php echo htmlspecialchars($t['nome_frente']); ?></span></td>
                <td><strong style="color: var(--texto-principal); font-weight: 800;"><?php echo htmlspecialchars($t['nome']); ?></strong></td>
                <td style="text-align: right;">
                  <form action="admin.php" method="POST" onsubmit="return confirm('ATENÇÃO: Apagar este módulo irá desalocar todas as aulas e questões vinculadas de forma irreversível!')">
                    <input type="hidden" name="acao" value="del_topico">
                    <input type="hidden" name="topico_id" value="<?php echo $t['id']; ?>">
                    <button type="submit" class="btn-deletar">🗑️ Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <script>
    // ==========================================
    // SISTEMA DE ABAS (INTERFACES)
    // ==========================================
    function alternarAba(evt, nomeAba) {
      document.querySelectorAll(".aba-painel").forEach(painel => painel.classList.remove("ativo"));
      document.querySelectorAll(".aba-link").forEach(btn => btn.classList.remove("ativa"));
      document.getElementById(nomeAba).classList.add("ativo");
      evt.currentTarget.classList.add("ativa");
    }

    // ==========================================
    // MENU DROPDOWN & TEMA ESCURO
    // ==========================================
    function alternarDropdown(id) {
      document.querySelectorAll('.dropdown-conteudo').forEach(drop => { if(drop.id !== id) drop.classList.remove('mostrar'); });
      document.getElementById(id).classList.toggle('mostrar');
    }

    window.onclick = function(event) {
        if (!event.target.matches('button') && !event.target.closest('button')) {
            document.querySelectorAll('.dropdown-conteudo').forEach(drop => drop.classList.remove('mostrar'));
        }
    }

    // Lógica do Dark Mode Preservada
    const body = document.body;
    if (localStorage.getItem('temaAtomicamente') === 'dark') {
      body.classList.add('dark-mode');
    }

    function alternarModoNoturno() {
      body.classList.toggle('dark-mode');
      if (body.classList.contains('dark-mode')) {
        localStorage.setItem('temaAtomicamente', 'dark');
      } else {
        localStorage.setItem('temaAtomicamente', 'light');
      }
    }

    // ==========================================
    // EDIÇÃO DINÂMICA: AULAS
    // ==========================================
    const aulasBD = <?php echo json_encode($aulas_json_data); ?>;

    function ativarEdicaoAula(id) {
      const aula = aulasBD[id];
      if (!aula) return;

      document.getElementById('aulaAcao').value = "edit_aula";
      document.getElementById('aulaId').value = id;
      document.getElementById('aulaTopico').value = aula.topico_id;
      document.getElementById('aulaTitulo').value = aula.titulo;
      document.getElementById('aulaVideo').value = aula.video_url || "";
      document.getElementById('aulaTexto').value = aula.resumo || "";

      document.getElementById('tituloFormAula').innerHTML = "✏️ Editando: " + aula.titulo;
      document.getElementById('btnSalvarAula').innerHTML = "💾 Salvar Alterações";
      document.getElementById('btnCancelarAula').style.display = "inline-block";

      document.getElementById('cardFormAula').scrollIntoView({ behavior: 'smooth' });
    }

    function cancelarEdicaoAula() {
      document.getElementById('formAula').reset();
      document.getElementById('aulaAcao').value = "add_aula";
      document.getElementById('aulaId').value = "";
      
      document.getElementById('tituloFormAula').innerHTML = "➕ Cadastrar Nova Aula";
      document.getElementById('btnSalvarAula').innerHTML = "Salvar Aula";
      document.getElementById('btnCancelarAula').style.display = "none";
    }

    // ==========================================
    // EDIÇÃO DINÂMICA: QUESTÕES
    // ==========================================
    const questoesDB = <?php echo json_encode($questoes_json_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    
    function ativarEdicaoQuestao(id) {
        const questao = questoesDB[id];
        if (!questao) return;

        document.getElementById('tituloFormQuestao').innerHTML = "✏️ Editando a Questão #" + id;
        document.getElementById('inputAcao').value = "edit_questao";
        document.getElementById('inputQuestaoId').value = id;

        document.getElementById('selectTopico').value = questao.subtopic_id;
        document.getElementById('selectAula').value = questao.aula_id || "";
        document.getElementById('textoEnunciado').value = questao.enunciado;

        let correta = "A";
        questao.alternativas.forEach(alt => {
            const letraLow = alt.letra.toLowerCase();
            const inputAlt = document.getElementById('alt_' + letraLow);
            if (inputAlt) { inputAlt.value = alt.texto_alternativa; }
            if (alt.eh_correta == 1) { correta = alt.letra; }
        });
        document.getElementById('selectCorreta').value = correta;

        document.getElementById('btnSalvarQuestao').innerHTML = "💾 Salvar Alterações";
        document.getElementById('btnCancelarEdicaoQuestao').style.display = "inline-block";
        document.getElementById('cardFormQuestao').scrollIntoView({ behavior: 'smooth' });
    }

    function cancelarEdicaoQuestao() {
        document.getElementById('formQuestao').reset();
        document.getElementById('tituloFormQuestao').innerHTML = "➕ Inserir Nova Questão";
        document.getElementById('inputAcao').value = "add_questao";
        document.getElementById('inputQuestaoId').value = "";
        
        document.getElementById('btnSalvarQuestao').innerHTML = "Publicar Questão";
        document.getElementById('btnCancelarEdicaoQuestao').style.display = "none";
    }

    function filtrarQuestoes() {
        const filtroTopico = document.getElementById('filtroTopico').value;
        const busca = document.getElementById('buscaQuestao').value.toLowerCase();
        const rows = document.querySelectorAll('.questao-row');

        rows.forEach(row => {
            const topicoQuestao = row.getAttribute('data-topico');
            const textoEnunciado = row.querySelector('.enunciado-alvo').textContent.toLowerCase();
            
            const atendeTopico = (filtroTopico === 'todos' || topicoQuestao === filtroTopico);
            const atendeBusca = (textoEnunciado.includes(busca));

            if (atendeTopico && atendeBusca) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
  </script>
</body>
</html>
