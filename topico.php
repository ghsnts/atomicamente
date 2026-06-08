<?php
// Inicia a sessão com segurança
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Proteção de Acesso
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$primeiro_nome = explode(' ', trim($_SESSION['user_nome'] ?? 'Estudante'))[0];
$slug_atual = isset($_GET['id']) ? $_GET['id'] : 'modelos-atomicos'; 

// =========================================================================================
// HELPER: FILTRAGEM ANTI-ACENTOS PARA O SISTEMA DE FOCO
// =========================================================================================
function removerAcentos($string) {
    return strtolower(preg_replace(['/(á|à|ã|â|ä)/', '/(Á|À|Ã|Â|Ä)/', '/(é|è|ê|ë)/', '/(É|È|Ê|Ë)/', '/(í|ì|î|ï)/', '/(Í|Ì|Î|Ï)/', '/(ó|ò|õ|ô|ö)/', '/(Ó|Ò|Õ|Ô|Ö)/', '/(ú|ù|û|ü)/', '/(Ú|Ù|Û|Ü)/', '/(ç)/', '/(Ç)/'], explode(' ','a A e E i I o O u U c C'), $string));
}

// =========================================================================================
// 1. RECEPTOR AJAX (SALVA O PROGRESSO E ATUALIZA META)
// =========================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_estudo_guiado'])) {
    $q_id = (int) $_POST['question_id'];
    $alt_id = (int) $_POST['alternative_id'];
    $is_correct = (int) $_POST['is_correct'];

    try {
        $stmtProg = $pdo->prepare("
            INSERT INTO user_progress (user_id, question_id, alternative_id, is_correct, foi_correta, respondido_em) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE alternative_id = ?, is_correct = ?, foi_correta = ?, respondido_em = CURRENT_TIMESTAMP
        ");
        $stmtProg->execute([$user_id, $q_id, $alt_id, $is_correct, $is_correct, $alt_id, $is_correct, $is_correct]);
        
        echo json_encode(['status' => 'sucesso']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'erro', 'msg' => $e->getMessage()]);
    }
    exit;
}

try {
    // =========================================================================================
    // 2. DADOS DO TÓPICO, FRENTE E DADOS DO ALUNO
    // =========================================================================================
    $stmtTopico = $pdo->prepare("
        SELECT t.*, f.nome as nome_frente, f.icone as icone_frente
        FROM topicos t 
        JOIN frentes f ON t.frente_id = f.id 
        WHERE t.slug = :slug
    ");
    $stmtTopico->execute([':slug' => $slug_atual]);
    $topico = $stmtTopico->fetch(PDO::FETCH_ASSOC);

    if (!$topico) {
        die("<h1>Tópico não encontrado!</h1><p>Volte ao <a href='materias.php'>Painel de Matérias</a>.</p>");
    }

    $stmtUser = $pdo->prepare("SELECT streak, meta_diaria, frente_foco FROM users WHERE id = :uid");
    $stmtUser->execute([':uid' => $user_id]);
    $dados_user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    
    $ofensiva = $dados_user['streak'] ?? 0;
    $meta_objetivo = $dados_user['meta_diaria'] ?? 5; 
    $foco_aluno = strtolower(trim($dados_user['frente_foco'] ?? ''));

    $nome_frente_limpo = removerAcentos($topico['nome_frente']);
    $topico_eh_foco = false;
    if (
        ($foco_aluno === 'geral' && strpos($nome_frente_limpo, 'geral') !== false) ||
        ($foco_aluno === 'fisico' && strpos($nome_frente_limpo, 'fisico') !== false) ||
        ($foco_aluno === 'organica' && strpos($nome_frente_limpo, 'organica') !== false) ||
        ($foco_aluno === 'ambiental' && strpos($nome_frente_limpo, 'ambiental') !== false)
    ) {
        $topico_eh_foco = true;
    }

    // =========================================================================================
    // 3. BARRA DE PROGRESSO DO TÓPICO E META DIÁRIA
    // =========================================================================================
    $stmtProgresso = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM questions q WHERE q.subtopic_id = :tid1) as total_questoes,
            (SELECT COUNT(DISTINCT up.question_id) FROM user_progress up JOIN questions q ON up.question_id = q.id WHERE q.subtopic_id = :tid2 AND up.user_id = :uid) as respondidas
    ");
    $stmtProgresso->execute([':tid1' => $topico['id'], ':tid2' => $topico['id'], ':uid' => $user_id]);
    $progresso = $stmtProgresso->fetch(PDO::FETCH_ASSOC);
    
    $total_q = $progresso['total_questoes'] ?? 0;
    $resp_q = $progresso['respondidas'] ?? 0;
    $porcentagem_progresso = $total_q > 0 ? round(($resp_q / $total_q) * 100) : 0;

    $stmtMeta = $pdo->prepare("SELECT COUNT(DISTINCT question_id) FROM user_progress WHERE user_id = :uid AND DATE(respondido_em) = CURDATE()");
    $stmtMeta->execute([':uid' => $user_id]);
    $questoes_hoje = $stmtMeta->fetchColumn() ?: 0;
    $porcentagem_meta = $meta_objetivo > 0 ? min(100, round(($questoes_hoje / $meta_objetivo) * 100)) : 100;

    // =========================================================================================
    // 4. AULAS E FONTES
    // =========================================================================================
    $stmtAulas = $pdo->prepare("SELECT * FROM aulas WHERE topico_id = :tid ORDER BY id ASC");
    $stmtAulas->execute([':tid' => $topico['id']]);
    $aulas = $stmtAulas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($aulas as &$aula) {
        $aula['fontes'] = [];
        try {
            $stmtFontes = $pdo->prepare("SELECT * FROM fontes_aula WHERE aula_id = :aid");
            $stmtFontes->execute([':aid' => $aula['id']]);
            $aula['fontes'] = $stmtFontes->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }

    // =========================================================================================
    // 5. BANCO DE QUESTÕES
    // =========================================================================================
    $stmtQ = $pdo->prepare("SELECT id, enunciado FROM questions WHERE subtopic_id = :tid ORDER BY id ASC");
    $stmtQ->execute([':tid' => $topico['id']]);
    $questoes_brutas = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

    $questoes_dados = [];
    foreach ($questoes_brutas as $q) {
        $stmtA = $pdo->prepare("SELECT id, letra, texto_alternativa, eh_correta FROM alternatives WHERE question_id = :qid ORDER BY letra ASC");
        $stmtA->execute([':qid' => $q['id']]);
        $q['alternativas'] = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        
        $stmtStatus = $pdo->prepare("SELECT is_correct FROM user_progress WHERE user_id = :uid AND question_id = :qid LIMIT 1");
        $stmtStatus->execute([':uid' => $user_id, ':qid' => $q['id']]);
        $status_resposta = $stmtStatus->fetch(PDO::FETCH_ASSOC);
        
        $q['status_aluno'] = $status_resposta ? ($status_resposta['is_correct'] ? 'correta' : 'errada') : 'pendente';
        $q['explicacao'] = "A resolução completa estará disponível em breve pelas administradoras.";
        $questoes_dados[] = $q;
    }

    // =========================================================================================
    // 6. ÁRVORE LATERAL: IRMÃOS + OUTRAS MATÉRIAS
    // =========================================================================================
    $stmtIrmaos = $pdo->prepare("SELECT id, nome, slug FROM topicos WHERE frente_id = :fid ORDER BY id ASC");
    $stmtIrmaos->execute([':fid' => $topico['frente_id']]);
    $topicos_irmaos = $stmtIrmaos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($topicos_irmaos as &$irmao) {
        $stmtProgIrmao = $pdo->prepare("
            SELECT 
                (SELECT COUNT(*) FROM questions WHERE subtopic_id = :tid1) as total,
                (SELECT COUNT(DISTINCT up.question_id) FROM user_progress up JOIN questions q ON up.question_id = q.id WHERE q.subtopic_id = :tid2 AND up.user_id = :uid) as resp
        ");
        $stmtProgIrmao->execute([':tid1' => $irmao['id'], ':tid2' => $irmao['id'], ':uid' => $user_id]);
        $progI = $stmtProgIrmao->fetch(PDO::FETCH_ASSOC);
        $tot = $progI['total'] ?? 0;
        $res = $progI['resp'] ?? 0;
        $irmao['porcentagem'] = $tot > 0 ? round(($res / $tot) * 100) : 0;
    }
    unset($irmao);

    // Pegando OUTRAS Frentes
    $stmtOutrasFrentes = $pdo->prepare("SELECT * FROM frentes WHERE id != :fid ORDER BY nome ASC");
    $stmtOutrasFrentes->execute([':fid' => $topico['frente_id']]);
    $outras_frentes = $stmtOutrasFrentes->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($outras_frentes as &$of) {
        $stmtTO = $pdo->prepare("SELECT id, nome, slug FROM topicos WHERE frente_id = :fid ORDER BY id ASC");
        $stmtTO->execute([':fid' => $of['id']]);
        $of['topicos'] = $stmtTO->fetchAll(PDO::FETCH_ASSOC);
        
        $of_nome_limpo = removerAcentos($of['nome']);
        $of['is_recomendada'] = false;
        if (
            ($foco_aluno === 'geral' && strpos($of_nome_limpo, 'geral') !== false) ||
            ($foco_aluno === 'fisico' && strpos($of_nome_limpo, 'fisico') !== false) ||
            ($foco_aluno === 'organica' && strpos($of_nome_limpo, 'organica') !== false) ||
            ($foco_aluno === 'ambiental' && strpos($of_nome_limpo, 'ambiental') !== false)
        ) {
            $of['is_recomendada'] = true;
        }
    }
    unset($of);

} catch (PDOException $e) {
    die("Erro Crítico no Banco: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($topico['nome']); ?> | Atomicamente</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    /* ================= VARIÁVEIS ================= */
    :root {
      --bg-global: #f8fafc; --bg-card: #ffffff; --borda: #e2e8f0;
      --texto-principal: #1e293b; --texto-secundario: #64748b;
      --roxo-base: #7c3aed; --verde: #10b981; --vermelho: #ef4444; --laranja: #f59e0b;
      --roxo-suave: rgba(139, 92, 246, 0.2);
    }
    [data-theme="dark"] {
      --bg-global: #0f172a; --bg-card: #1e293b; --borda: #334155;
      --texto-principal: #f8fafc; --texto-secundario: #94a3b8;
      --roxo-base: #8b5cf6;
      --roxo-suave: rgba(139, 92, 246, 0.4);
    }

    body { font-family: 'Inter', sans-serif; background: var(--bg-global); color: var(--texto-principal); margin: 0; padding: 0; overflow-x: hidden; }

    /* ================= HEADER ================= */
    .topo-dash { border-bottom: 1px solid var(--borda); background: var(--bg-card); position: fixed; top: 0; width: 100%; z-index: 1000; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .nav-dash { padding: 12px 20px; max-width: 1300px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; }
    .marca-dash { font-weight: 800; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--texto-principal); letter-spacing: -0.03em; }
    .badge-enem { font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; color: white; background: var(--roxo-base); letter-spacing: 0.05em; }

    .menu-dropdown { position: relative; display: inline-block; }
    .dropdown-conteudo { display: none; position: absolute; right: 0; top: 40px; background-color: var(--bg-card); min-width: 200px; box-shadow: 0px 8px 20px 0px rgba(0,0,0,0.1); border: 1px solid var(--borda); border-radius: 12px; z-index: 1001; overflow: hidden; animation: dropAnim 0.2s ease forwards;}
    @keyframes dropAnim { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .dropdown-conteudo.mostrar { display: block; }
    .dropdown-item { color: var(--texto-principal); padding: 12px 16px; text-decoration: none; display: block; font-size: 0.9rem; font-weight: 500; transition: background 0.2s; cursor: pointer; }
    .dropdown-item:hover { background-color: var(--bg-global); color: var(--roxo-base); }
    .dropdown-divisor { border-top: 1px solid var(--borda); margin: 4px 0; }

    /* ================= SIDEBAR ================= */
    .layout-imersivo { display: flex; height: 100vh; padding-top: 60px; box-sizing: border-box; }
    
    .sidebar-magica {
        width: 75px; background: var(--bg-card); border-right: 1px solid var(--borda);
        height: calc(100vh - 60px); position: fixed; left: 0; top: 60px;
        transition: width 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        overflow-y: auto; overflow-x: hidden; z-index: 900;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .sidebar-magica:hover { width: 340px; box-shadow: 10px 0 30px rgba(0,0,0,0.05); }
    .sidebar-magica::-webkit-scrollbar { width: 6px; }
    .sidebar-magica::-webkit-scrollbar-thumb { background: var(--borda); border-radius: 4px; }
    
    .sidebar-inner { padding: 20px 0; width: 340px; }
    .menu-titulo { font-weight: 800; font-size: 0.8rem; text-transform: uppercase; color: var(--texto-secundario); margin: 0 0 10px 20px; display: flex; align-items: center; gap: 15px;}
    .menu-item { display: flex; align-items: center; padding: 12px 20px; color: var(--texto-principal); text-decoration: none; font-weight: 600; font-size: 0.95rem; border-left: 3px solid transparent; white-space: nowrap; transition: all 0.2s;}
    .menu-item:hover { background: var(--bg-global); color: var(--roxo-base); }
    .menu-item.ativo { background: rgba(124, 58, 237, 0.06); border-left-color: var(--roxo-base); color: var(--roxo-base); }
    .menu-icon { font-size: 1.3rem; min-width: 35px; text-align: center; }

    .mini-barra-progresso { background: var(--borda); height: 6px; border-radius: 4px; overflow: hidden; flex: 1; }
    .mini-barra-preenchida { background: var(--roxo-base); height: 100%; border-radius: 4px; }

    /* Accordion */
    .frente-accordion { border-top: 1px solid var(--borda); }
    .frente-header { padding: 12px 20px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-weight: 700; color: var(--texto-principal); font-size: 0.9rem; transition: background 0.2s; white-space: nowrap;}
    .frente-header:hover { background: var(--bg-global); }
    .frente-header-left { display: flex; align-items: center; gap: 15px; }
    .frente-conteudo { display: none; background: rgba(0,0,0,0.02); padding: 5px 0; }
    .frente-conteudo a { display: block; padding: 10px 20px 10px 70px; font-size: 0.85rem; color: var(--texto-secundario); text-decoration: none; font-weight: 500; transition: color 0.2s; white-space: nowrap; text-overflow: ellipsis; overflow: hidden;}
    .frente-conteudo a:hover { color: var(--roxo-base); background: rgba(124, 58, 237, 0.05); }
    
    .badge-recomendada { background: linear-gradient(45deg, #f59e0b, #ef4444); color: white; padding: 3px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; margin-left: 8px; box-shadow: 0 0 10px rgba(245, 158, 11, 0.4);}

    /* Meta Diária */
    .widget-meta { padding: 20px; width: 340px; box-sizing: border-box; border-top: 1px solid var(--borda); background: linear-gradient(180deg, transparent, rgba(124, 58, 237, 0.03));}
    .meta-card { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 16px; padding: 15px; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .meta-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: var(--roxo-base); border-radius: 4px 0 0 4px; }
    .meta-topo { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .meta-titulo-txt { font-size: 0.8rem; font-weight: 800; color: var(--texto-secundario); text-transform: uppercase; letter-spacing: 0.05em;}
    .meta-icone { background: rgba(124, 58, 237, 0.1); color: var(--roxo-base); padding: 5px; border-radius: 8px; font-size: 0.9rem;}
    .meta-info { font-size: 1.1rem; font-weight: 800; color: var(--texto-principal); margin-bottom: 8px;}
    .meta-info span { color: var(--texto-secundario); font-size: 0.9rem; font-weight: 600;}
    .meta-bar-bg { width: 100%; height: 8px; background: var(--bg-global); border-radius: 4px; overflow: hidden; border: 1px solid var(--borda);}
    .meta-bar-fill { height: 100%; background: linear-gradient(90deg, #8b5cf6, #3b82f6); border-radius: 4px; transition: width 0.8s ease; }

    /* ================= MAIN CONTENT ================= */
    .main-content { flex: 1; margin-left: 75px; padding: 40px; overflow-y: auto; scroll-behavior: smooth; position: relative;}
    .content-wrapper { max-width: 900px; margin: 0 auto; }

    .topico-badge { background: rgba(124, 58, 237, 0.1); color: var(--roxo-base); padding: 6px 16px; border-radius: 20px; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: inline-block; margin-bottom: 15px; border: 1px solid rgba(124, 58, 237, 0.2); }
    .topico-h1 { font-size: 2.8rem; font-weight: 900; margin: 0 0 40px 0; color: var(--texto-principal); line-height: 1.1; letter-spacing: -0.03em;}

    .video-cinematico { background: #000; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.1); margin-bottom: 25px; position: relative; aspect-ratio: 16 / 9; width: 100%; border: 1px solid var(--borda); }
    .video-cinematico iframe { width: 100%; height: 100%; border: none; }

    .aula-bloco { margin-bottom: 60px; }
    .aula-titulo-h2 { font-size: 1.8rem; margin: 0 0 20px 0; font-weight: 800; color: var(--texto-principal); display: flex; align-items: center; gap: 10px; }
    .aula-teoria { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 20px; padding: 35px; font-size: 1.1rem; line-height: 1.7; color: var(--texto-secundario); margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
    
    .aula-fontes { background: rgba(100, 116, 139, 0.05); border: 1px dashed var(--borda); border-radius: 14px; padding: 20px; }
    .aula-fontes-titulo { font-size: 0.9rem; font-weight: 800; color: var(--texto-secundario); text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;}
    .fonte-item { font-size: 0.95rem; color: var(--texto-principal); margin-bottom: 5px; display: flex; align-items: center; gap: 8px; font-weight: 500;}
    .fonte-item a { color: var(--roxo-base); text-decoration: none; font-weight: 600; }

    /* ================= MOTOR DE QUESTÕES ================= */
    .engine-questoes { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 24px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
    .engine-header { border-bottom: 2px solid var(--borda); padding-bottom: 25px; margin-bottom: 30px; }
    .engine-header h2 { margin: 0 0 10px 0; font-size: 1.8rem; font-weight: 900; color: var(--texto-principal); }
    
    .progresso-container { margin-top: 10px; }
    .prog-text { font-size: 0.9rem; color: var(--texto-secundario); font-weight: 600; margin-bottom: 8px; display: flex; justify-content: space-between;}
    .prog-bar-bg { width: 100%; height: 10px; background: var(--bg-global); border-radius: 10px; overflow: hidden; border: 1px solid var(--borda);}
    .prog-bar-fill { height: 100%; background: linear-gradient(90deg, var(--roxo-base), #4f46e5); border-radius: 10px; transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1); }

    .questao-card { margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid var(--borda); }
    .questao-card:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
    .q-enunciado { font-size: 1.1rem; font-weight: 600; line-height: 1.6; margin-bottom: 20px; color: var(--texto-principal); text-align: justify;}
    .q-alts { display: flex; flex-direction: column; gap: 10px; }
    
    .q-alt-btn {
        background: var(--bg-global); border: 2px solid var(--borda); padding: 15px; border-radius: 12px; 
        text-align: left; font-size: 1rem; color: var(--texto-principal); cursor: pointer; transition: all 0.2s; font-weight: 500;
        display: flex; gap: 15px; align-items: center; line-height: 1.4;
    }
    .q-alt-btn .letra { font-weight: 800; color: var(--texto-secundario); background: var(--bg-card); padding: 4px 10px; border-radius: 6px; border: 1px solid var(--borda); }
    .q-alt-btn:hover { border-color: var(--roxo-base); transform: translateX(4px); }
    .q-alt-btn.correct { background: rgba(16,185,129,0.1) !important; border-color: var(--verde) !important; color: var(--verde); }
    .q-alt-btn.wrong { background: rgba(239,68,68,0.1) !important; border-color: var(--vermelho) !important; color: var(--vermelho); opacity: 0.7;}
    .q-alt-btn.correct .letra { background: var(--verde); color: white; border-color: var(--verde); }
    
    .q-feedback { display: none; margin-top: 20px; padding: 20px; border-radius: 12px; font-size: 0.95rem; line-height: 1.6; font-weight: 500; }
    .badge-status { font-size: 0.75rem; padding: 4px 10px; border-radius: 8px; font-weight: 800; text-transform: uppercase; margin-bottom: 12px; display: inline-block; letter-spacing: 0.05em;}
    .status-correta { background: rgba(16,185,129,0.1); color: var(--verde); border: 1px solid rgba(16,185,129,0.3);}
    .status-errada { background: rgba(239,68,68,0.1); color: var(--vermelho); border: 1px solid rgba(239,68,68,0.3);}

    /* ================= JANELAS FLUTUANTES (CALCULADORA & RASCUNHO) ================= */
    .janela-flutuante { position: fixed; background: var(--bg-global); border: 1px solid var(--borda); border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.4); z-index: 10000; display: none; flex-direction: column; overflow: hidden; transition: background-color 0.3s, border-color 0.3s; }
    .janela-maximizada { top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; border-radius: 0; }
    .janela-maximizada .resizer { display: none; }

    .resizer { position: absolute; z-index: 10; }
    .resizer-r { right: 0; top: 0; width: 10px; height: 100%; cursor: ew-resize; }
    .resizer-b { bottom: 0; left: 0; width: 100%; height: 10px; cursor: ns-resize; }
    .resizer-br { right: 0; bottom: 0; width: 20px; height: 20px; cursor: nwse-resize; }

    .janela-header { background: var(--bg-card); padding: 12px 20px; cursor: grab; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--borda); user-select: none; }
    .janela-header:active { cursor: grabbing; }
    
    .controles-janela { display: flex; gap: 8px; }
    .controles-janela button { background: var(--bg-global); border: 1px solid var(--borda); color: var(--texto-secundario); border-radius: 8px; width: 32px; height: 32px; font-size: 1rem; cursor: pointer; transition: 0.2s; display: flex; justify-content: center; align-items: center; }
    .controles-janela button:hover { color: var(--roxo-base); border-color: var(--roxo-base); background: rgba(139, 92, 246, 0.1); }
    .btn-fechar-janela:hover { color: white !important; border-color: #ef4444 !important; background: #ef4444 !important; }

    /* RASCUNHO */
    #janelaRascunho { top: 15vh; left: 10vw; width: 700px; height: 500px; min-width: 400px; min-height: 350px; }
    .ferramentas-desenho { background: var(--bg-card); padding: 12px 15px; display: flex; gap: 10px; align-items: center; border-bottom: 1px solid var(--borda); flex-wrap: wrap;}
    .btn-ferramenta { background: var(--bg-global); border: 1px solid var(--borda); padding: 8px 12px; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; color: var(--texto-principal); transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
    .btn-ferramenta:hover { background: var(--borda); }
    .btn-ferramenta.ativo { background: rgba(139, 92, 246, 0.1); border-color: var(--roxo-base); color: var(--roxo-base); }
    
    .cor-bolinha { width: 14px; height: 14px; border-radius: 50%; display: inline-block; box-shadow: inset 0 0 0 1px rgba(0,0,0,0.2); }
    .btn-cor { padding: 8px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; background: transparent; transition: 0.2s; display: flex; justify-content: center; align-items: center; }
    .btn-cor:hover { background: var(--borda); }
    .btn-cor.ativo { border-color: var(--roxo-base); background: rgba(139, 92, 246, 0.1); }

    .area-canvas { flex-grow: 1; background-color: #ffffff; cursor: crosshair; position: relative; overflow: hidden; } 
    #canvasRascunho { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    .caderno-footer { background: var(--bg-card); padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--borda); }

    /* CALCULADORA */
    #janelaCalculadora { top: 20vh; right: 5vw; width: 380px; height: 500px; min-width: 320px; min-height: 480px; }
    .calc-body { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; }
    .calc-display { background: var(--bg-global); border: 1px solid var(--borda); height: 70px; min-height: 70px; border-radius: 16px; margin-bottom: 15px; text-align: right; font-size: 1.8rem; font-weight: 800; padding: 0 20px; display: flex; align-items: center; justify-content: flex-end; color: var(--texto-principal); overflow: hidden; letter-spacing: 1px; }
    .calc-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; flex-grow: 1; }
    .calc-btn { background: var(--bg-global); border: 1px solid var(--borda); border-radius: 12px; font-size: 1.1rem; font-weight: 700; color: var(--texto-principal); cursor: pointer; transition: all 0.1s; padding: 0; display: flex; align-items: center; justify-content: center;}
    .calc-btn:active { transform: scale(0.92); }
    .calc-btn.op { background: rgba(139, 92, 246, 0.05); color: var(--roxo-base); border-color: rgba(139, 92, 246, 0.2); }
    .calc-btn.ci { background: rgba(16, 185, 129, 0.05); color: #10b981; border-color: rgba(16, 185, 129, 0.2); font-size: 0.95rem; }
    .calc-btn.igual { background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; grid-column: span 2; }

    /* FAB BOTAO PARA ABRIR AS FERRAMENTAS */
    .fab-container { position: fixed; bottom: 30px; right: 30px; display: flex; flex-direction: column; gap: 15px; align-items: flex-end; z-index: 9999; }
    .fab-btn { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, var(--roxo-base), #4f46e5); color: white; border: none; box-shadow: 0 10px 25px rgba(139, 92, 246, 0.4); font-size: 1.5rem; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: transform 0.2s; }
    .fab-btn:hover { transform: scale(1.1); }
    .fab-secundario { width: 50px; height: 50px; background: var(--bg-card); color: var(--texto-principal); border: 1px solid var(--borda); font-size: 1.2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

    @media (max-width: 900px) {
        .sidebar-magica { display: none; }
        .main-content { margin-left: 0; padding: 30px 20px; }
    }
  </style>
</head>
<body class="dash-body">

  <header class="topo-dash">
    <div class="nav-dash">
      <a href="dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 30px; border-radius: 6px;" />
        Atomicamente <span class="badge-enem">SALA DE AULA</span>
      </a>
      
      <div style="display: flex; align-items: center; gap: 15px;">
        <?php if (function_exists('verificarSeEhAdmin') && verificarSeEhAdmin()): ?>
          <a href="admin.php" style="background: var(--roxo-base); color: white; padding: 6px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none; font-weight: 700;">⚙️ Admin</a>
        <?php endif; ?>

        <a href="materias.php" style="color: var(--texto-secundario); text-decoration: none; font-weight: 700; font-size: 0.9rem;">📚 Matérias</a>

        <div style="display: flex; align-items: center; gap: 6px; background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.3); padding: 6px 12px; border-radius: 8px; font-weight: 800; color: #ea580c; font-size: 0.85rem;">
          🔥 <?php echo $ofensiva; ?> Dias
        </div>
          
        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-config')" style="background: none; border: 1px solid var(--borda); color: var(--texto-principal); padding: 6px 10px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; cursor: pointer;">🛠️</button>
          <div id="drop-config" class="dropdown-conteudo">
            <div class="dropdown-item" onclick="alternarModoNoturno()">🌙 Alternar Tema</div>
          </div>
        </div>

        <div class="menu-dropdown">
          <button onclick="alternarDropdown('drop-perfil')" style="background: var(--roxo-base); color: white; border: none; padding: 6px 12px; font-size: 0.85rem; border-radius: 8px; font-weight: 700; cursor: pointer;">👤 <?php echo $primeiro_nome; ?> ▼</button>
          <div id="drop-perfil" class="dropdown-conteudo">
            <div style="padding: 10px; font-size: 0.75rem; color: var(--texto-secundario); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Minha Conta</div>
            <a href="perfil.php" class="dropdown-item">🧑‍🎓 Editar Perfil</a>
            <a href="progresso.php" class="dropdown-item">📈 Meu Progresso</a>
            <div class="dropdown-divisor"></div>
            <a href="logout.php" class="dropdown-item" style="color: #ef4444;">🚪 Sair</a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="layout-imersivo">
    
    <aside class="sidebar-magica">
        <div class="sidebar-inner">
            
            <div style="margin-bottom: 20px;">
                <div class="menu-titulo"><span class="menu-icon">▶</span> Índice da Aula</div>
                <?php foreach($aulas as $a): ?>
                    <a href="#aula_<?php echo $a['id']; ?>" class="menu-item">
                        <span class="menu-icon">🎥</span> <?php echo htmlspecialchars($a['titulo']); ?>
                    </a>
                <?php endforeach; ?>
                <a href="#exercicios" class="menu-item">
                    <span class="menu-icon">📝</span> Estudo Guiado
                </a>
            </div>

            <div style="border-top: 1px solid var(--borda); padding-top: 20px; margin-bottom: 20px;">
                <div class="menu-titulo">
                    <span class="menu-icon"><?php echo $topico['icone_frente']; ?></span> Matéria Atual
                    <?php if($topico_eh_foco): ?>
                        <span class="badge-recomendada">⭐ RECOMENDADO</span>
                    <?php endif; ?>
                </div>
                
                <?php foreach($topicos_irmaos as $irmao): ?>
                    <a href="topico.php?id=<?php echo $irmao['slug']; ?>" class="menu-item <?php echo $irmao['id'] == $topico['id'] ? 'ativo' : ''; ?>" style="font-size: 0.85rem; flex-direction: column; align-items: flex-start; padding: 12px 20px;">
                        <div style="display: flex; align-items: center; width: 100%;">
                            <span class="menu-icon" style="font-size: 1rem; min-width: 25px;">•</span> 
                            <span style="white-space: normal; line-height: 1.2;"><?php echo htmlspecialchars($irmao['nome']); ?></span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; width: 100%; padding-left: 25px; margin-top: 6px; box-sizing: border-box;">
                            <div class="mini-barra-progresso"><div class="mini-barra-preenchida" style="width: <?php echo $irmao['porcentagem']; ?>%;"></div></div>
                            <span style="font-size: 0.7rem; color: var(--texto-secundario); font-weight: 700;"><?php echo $irmao['porcentagem']; ?>%</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <div style="border-top: 1px solid var(--borda); padding-top: 20px;">
                <div class="menu-titulo"><span class="menu-icon">🧭</span> Explorar Matérias</div>
                <?php foreach($outras_frentes as $of): ?>
                    <div class="frente-accordion">
                        <div class="frente-header" onclick="toggleAccordion('frente_<?php echo $of['id']; ?>')">
                            <div class="frente-header-left">
                                <span><?php echo $of['icone'] ?? '📚'; ?></span>
                                <span><?php echo htmlspecialchars($of['nome']); ?></span>
                                <?php if($of['is_recomendada']): ?>
                                    <span class="badge-recomendada">⭐ RECOMENDADO</span>
                                <?php endif; ?>
                            </div>
                            <span style="color: var(--texto-secundario); font-size: 0.7rem;">▼</span>
                        </div>
                        <div class="frente-conteudo" id="frente_<?php echo $of['id']; ?>">
                            <?php foreach($of['topicos'] as $tOutro): ?>
                                <a href="topico.php?id=<?php echo $tOutro['slug']; ?>">- <?php echo htmlspecialchars($tOutro['nome']); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <div class="widget-meta">
            <div class="meta-card">
                <div class="meta-topo">
                    <span class="meta-titulo-txt">Meta Diária</span>
                    <span class="meta-icone">🎯</span>
                </div>
                <div class="meta-info" id="txtMeta">
                    <span id="valorMeta"><?php echo $questoes_hoje; ?></span> / <?php echo $meta_objetivo; ?> <span>questões</span>
                </div>
                <div class="meta-bar-bg">
                    <div class="meta-bar-fill" id="barraMeta" style="width: <?php echo $porcentagem_meta; ?>%;"></div>
                </div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div class="content-wrapper">
            
            <div class="topico-badge"><?php echo htmlspecialchars($topico['nome_frente']); ?></div>
            <h1 class="topico-h1"><?php echo htmlspecialchars($topico['nome']); ?></h1>

            <?php if(empty($aulas)): ?>
                <div class="aula-teoria">
                    <p>As aulas teóricas para este tópico estão sendo cadastradas pelas administradoras. Siga para o banco de exercícios!</p>
                </div>
            <?php else: ?>
                <?php foreach($aulas as $a): ?>
                    <div class="aula-bloco" id="aula_<?php echo $a['id']; ?>">
                        <h2 class="aula-titulo-h2">🎥 <?php echo htmlspecialchars($a['titulo']); ?></h2>

                        <?php if (!empty($a['video_url'])): ?>
                        <div class="video-cinematico">
                            <iframe src="<?php echo htmlspecialchars($a['video_url']); ?>" allowfullscreen></iframe>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($a['resumo'])): ?>
                        <div class="aula-teoria">
                            <?php echo nl2br(htmlspecialchars($a['resumo'])); ?>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($a['fontes'])): ?>
                        <div class="aula-fontes">
                            <div class="aula-fontes-titulo">📚 Referências Utilizadas Nesta Aula</div>
                            <?php foreach($a['fontes'] as $fonte): ?>
                                <div class="fonte-item">
                                    • 
                                    <?php if(!empty($fonte['link'])): ?>
                                        <a href="<?php echo htmlspecialchars($fonte['link']); ?>" target="_blank"><?php echo htmlspecialchars($fonte['descricao']); ?></a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($fonte['descricao']); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="engine-questoes" id="exercicios">
                <div class="engine-header">
                    <h2>Estudo Guiado (Fixação)</h2>
                    <div class="progresso-container">
                        <div class="prog-text" id="txtProgresso">
                            <span>Mapeamento de Competência</span>
                            <span><?php echo $resp_q; ?> / <?php echo $total_q; ?> (<?php echo $porcentagem_progresso; ?>%)</span>
                        </div>
                        <div class="prog-bar-bg">
                            <div class="prog-bar-fill" id="barraProgresso" style="width: <?php echo $porcentagem_progresso; ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div>
                    <?php if(empty($questoes_dados)): ?>
                        <p style="text-align: center; color: var(--texto-secundario);">Nenhuma questão cadastrada no banco de dados para este tópico.</p>
                    <?php else: ?>
                        <?php foreach($questoes_dados as $index => $q): ?>
                            <div class="questao-card" id="card_q_<?php echo $q['id']; ?>">
                                <?php if($q['status_aluno'] == 'correta'): ?>
                                    <div class="badge-status status-correta">✓ Você já acertou esta questão</div>
                                <?php elseif($q['status_aluno'] == 'errada'): ?>
                                    <div class="badge-status status-errada">⚠ Você errou esta questão no passado</div>
                                <?php endif; ?>

                                <div class="q-enunciado"><strong><?php echo ($index+1); ?>.</strong> <?php echo nl2br(htmlspecialchars($q['enunciado'])); ?></div>
                                <div class="q-alts" id="alts_q_<?php echo $q['id']; ?>">
                                    <?php foreach($q['alternativas'] as $alt): ?>
                                        <button class="q-alt-btn" onclick="verificarEstudoGuiado(this, <?php echo $q['id']; ?>, <?php echo $alt['id']; ?>, <?php echo $alt['eh_correta']; ?>)">
                                            <span class="letra"><?php echo $alt['letra']; ?></span>
                                            <span class="texto"><?php echo htmlspecialchars($alt['texto_alternativa']); ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="q-feedback" id="feed_q_<?php echo $q['id']; ?>">
                                    <strong>💡 Explicação:</strong> <span class="feed-txt"><?php echo htmlspecialchars($q['explicacao'] ?? 'Nenhuma explicação cadastrada.'); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </main>

    <div class="fab-container">
        <button type="button" class="fab-btn fab-secundario" onclick="abrirJanela('janelaCalculadora')" title="Calculadora Científica">🧮</button>
        <button type="button" class="fab-btn" onclick="abrirJanela('janelaRascunho')" title="Caderno de Rascunho">📝</button>
    </div>

    <div class="janela-flutuante" id="janelaRascunho">
        <div class="resizer resizer-r"></div>
        <div class="resizer resizer-b"></div>
        <div class="resizer resizer-br"></div>
        <div class="janela-header" id="headerRascunho">
            <div style="font-weight: 800; color: var(--texto-principal);">📝 Caderno Infinito</div>
            <div class="controles-janela">
                <button type="button" onclick="maximizarJanela('janelaRascunho')" title="Maximizar/Restaurar">🗖</button>
                <button type="button" class="btn-fechar-janela" onclick="fecharJanela('janelaRascunho')" title="Fechar">✖</button>
            </div>
        </div>
        <div class="ferramentas-desenho">
            <button type="button" class="btn-cor ativo" onclick="mudarCor('black', this)" title="Preto"><span class="cor-bolinha" style="background: black;"></span></button>
            <button type="button" class="btn-cor" onclick="mudarCor('#3b82f6', this)" title="Azul"><span class="cor-bolinha" style="background: #3b82f6;"></span></button>
            <button type="button" class="btn-cor" onclick="mudarCor('#ef4444', this)" title="Vermelho"><span class="cor-bolinha" style="background: #ef4444;"></span></button>
            <div style="width: 1px; height: 20px; background: var(--borda); margin: 0 5px;"></div>
            <button type="button" class="btn-ferramenta ativo" onclick="setTool('lapis', this)">✏️ Lápis</button>
            <button type="button" class="btn-ferramenta" onclick="setTool('linha', this)">📏 Linha</button>
            <button type="button" class="btn-ferramenta" onclick="setTool('retangulo', this)">🟦 Retângulo</button>
            <button type="button" class="btn-ferramenta" onclick="setTool('circulo', this)">⭕ Círculo</button>
            <button type="button" class="btn-ferramenta" onclick="setTool('borracha', this)">🧽 Borracha</button>
        </div>
        <div class="area-canvas" id="canvasContainer">
            <canvas id="canvasRascunho"></canvas>
        </div>
        <div class="caderno-footer">
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" class="btn-ferramenta" onclick="mudarPagina(-1)">⬅️ Ant</button>
                <span style="font-weight: 700; font-size: 0.9rem;" id="indicadorPagina">Pág 1</span>
                <button type="button" class="btn-ferramenta" onclick="mudarPagina(1)">Próx ➡️</button>
            </div>
            <button type="button" class="btn-ferramenta" style="color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="limparPagina()">🗑️ Limpar</button>
        </div>
    </div>

    <div class="janela-flutuante" id="janelaCalculadora">
        <div class="resizer resizer-r"></div>
        <div class="resizer resizer-b"></div>
        <div class="resizer resizer-br"></div>
        <div class="janela-header" id="headerCalculadora">
            <div style="font-weight: 800; color: var(--texto-principal);">🧮 Calculadora Científica</div>
            <div class="controles-janela">
                <button type="button" class="btn-fechar-janela" onclick="fecharJanela('janelaCalculadora')" title="Fechar">✖</button>
            </div>
        </div>
        <div class="calc-body">
            <div class="calc-display" id="calcDisplay">0</div>
            <div class="calc-grid">
                <button type="button" class="calc-btn ci" onclick="calcDigitar('sin(')">sin</button>
                <button type="button" class="calc-btn ci" onclick="calcDigitar('cos(')">cos</button>
                <button type="button" class="calc-btn ci" onclick="calcDigitar('tan(')">tan</button>
                <button type="button" class="calc-btn ci" onclick="calcDigitar('log(')">log</button>
                <button type="button" class="calc-btn op" onclick="calcLimpar()">C</button>
                
                <button type="button" class="calc-btn ci" onclick="calcDigitar('√(')">√</button>
                <button type="button" class="calc-btn ci" onclick="calcDigitar('^')">xʸ</button>
                <button type="button" class="calc-btn op" onclick="calcDigitar('(')">(</button>
                <button type="button" class="calc-btn op" onclick="calcDigitar(')')">)</button>
                <button type="button" class="calc-btn op" onclick="calcDigitar('/')">÷</button>
                
                <button type="button" class="calc-btn ci" onclick="calcDigitar('π')">π</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('7')">7</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('8')">8</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('9')">9</button>
                <button type="button" class="calc-btn op" onclick="calcDigitar('*')">×</button>
                
                <button type="button" class="calc-btn ci" onclick="calcDigitar('e')">e</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('4')">4</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('5')">5</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('6')">6</button>
                <button type="button" class="calc-btn op" onclick="calcDigitar('-')">-</button>
                
                <button type="button" class="calc-btn op" onclick="calcDel()">⌫</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('1')">1</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('2')">2</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('3')">3</button>
                <button type="button" class="calc-btn op" onclick="calcDigitar('+')">+</button>
                
                <button type="button" class="calc-btn" onclick="calcDigitar('0')">0</button>
                <button type="button" class="calc-btn" onclick="calcDigitar('.')">.</button>
                <button type="button" class="calc-btn" onclick="calcDigitar(',')">,</button>
                <button type="button" class="calc-btn igual" onclick="calcCalcular()">=</button>
            </div>
        </div>
    </div>

  </div>

  <script>
    // ==========================================
    // INTERFACE BÁSICA
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
    function alternarModoNoturno() {
        const doc = document.documentElement;
        if (doc.getAttribute('data-theme') === 'dark') { doc.removeAttribute('data-theme'); localStorage.setItem('temaAtomicamente', 'light'); } 
        else { doc.setAttribute('data-theme', 'dark'); localStorage.setItem('temaAtomicamente', 'dark'); }
    }
    if (localStorage.getItem('temaAtomicamente') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); }

    // ==========================================
    // ACCORDION (OUTRAS MATÉRIAS)
    // ==========================================
    function toggleAccordion(id) {
        const conteudo = document.getElementById(id);
        if (conteudo.style.display === "block") {
            conteudo.style.display = "none";
        } else {
            document.querySelectorAll('.frente-conteudo').forEach(el => el.style.display = 'none');
            conteudo.style.display = "block";
        }
    }

    // ==========================================
    // LÓGICA AJAX: ESTUDO GUIADO & META DIÁRIA
    // ==========================================
    let questoesTotais = <?php echo $total_q; ?>;
    let questoesResolvidas = <?php echo $resp_q; ?>;
    
    let metaResolvidasHoje = <?php echo $questoes_hoje; ?>;
    let metaObjetivo = <?php echo $meta_objetivo; ?>;

    function atualizarBarrasJS() {
        if(questoesTotais > 0) {
            const pct = Math.round((questoesResolvidas / questoesTotais) * 100);
            document.getElementById('barraProgresso').style.width = pct + '%';
            document.getElementById('txtProgresso').innerHTML = `<span>Mapeamento de Competência</span><span>${questoesResolvidas} / ${questoesTotais} (${pct}%)</span>`;
        }
        
        metaResolvidasHoje++;
        document.getElementById('valorMeta').innerText = metaResolvidasHoje;
        
        if(metaObjetivo > 0) {
            const pctMeta = Math.min(100, Math.round((metaResolvidasHoje / metaObjetivo) * 100));
            document.getElementById('barraMeta').style.width = pctMeta + '%';
        } else {
            document.getElementById('barraMeta').style.width = '100%';
        }
    }

    function verificarEstudoGuiado(btn, question_id, alternative_id, is_correta) {
        const containerAlts = document.getElementById('alts_q_' + question_id);
        const botoes = containerAlts.querySelectorAll('.q-alt-btn');
        const feedback = document.getElementById('feed_q_' + question_id);
        const card = document.getElementById('card_q_' + question_id);

        botoes.forEach(b => { b.style.pointerEvents = 'none'; b.style.opacity = '0.6'; });

        if (is_correta == 1) {
            btn.classList.add('correct'); btn.style.opacity = '1';
            feedback.style.background = 'rgba(16,185,129,0.08)'; feedback.style.color = 'var(--verde)'; feedback.style.border = '1px solid rgba(16,185,129,0.3)';
        } else {
            btn.classList.add('wrong'); btn.style.opacity = '1';
            botoes.forEach(b => { if(b.getAttribute('onclick').includes(', 1)')) { b.classList.add('correct'); b.style.opacity = '1'; } });
            feedback.style.background = 'rgba(239,68,68,0.08)'; feedback.style.color = 'var(--vermelho)'; feedback.style.border = '1px solid rgba(239,68,68,0.3)';
        }
        feedback.style.display = 'block';

        const hasBadge = card.querySelector('.badge-status');
        if (!hasBadge) {
            questoesResolvidas++;
            atualizarBarrasJS();
            
            const badge = document.createElement('div');
            badge.className = is_correta == 1 ? 'badge-status status-correta' : 'badge-status status-errada';
            badge.innerText = is_correta == 1 ? '✓ Progresso Salvo: Acerto' : '⚠ Progresso Salvo: Erro';
            card.insertBefore(badge, card.firstChild);
        }

        const formData = new FormData();
        formData.append('ajax_estudo_guiado', '1');
        formData.append('question_id', question_id);
        formData.append('alternative_id', alternative_id);
        formData.append('is_correct', is_correta);

        fetch('topico.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => { if(data.status !== 'sucesso') console.error('Erro ao salvar progresso:', data.msg); })
            .catch(error => console.error('Erro na requisição AJAX:', error));
    }

    // =============================================================
    // JANELAS FLUTUANTES (CALCULADORA E RASCUNHO IGUAL PROVA.PHP)
    // =============================================================
    let zIndexCounter = 10000;
    
    function abrirJanela(id) {
        const win = document.getElementById(id);
        win.style.display = 'flex';
        win.style.zIndex = ++zIndexCounter;
        if(id === 'janelaRascunho') setTimeout(redimensionarCanvas, 50);
    }
    
    function fecharJanela(id) { document.getElementById(id).style.display = 'none'; }
    
    function maximizarJanela(id) {
        const win = document.getElementById(id);
        win.classList.toggle('janela-maximizada');
        if(id === 'janelaRascunho') setTimeout(redimensionarCanvas, 50);
    }

    function tornarArrastavel(win, header) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        win.addEventListener('mousedown', () => { win.style.zIndex = ++zIndexCounter; });
        header.onmousedown = function(e) {
            if (win.classList.contains('janela-maximizada') || e.target.tagName === 'BUTTON') return;
            e.preventDefault();
            pos3 = e.clientX;
            pos4 = e.clientY;
            document.onmouseup = fecharArrasto;
            document.onmousemove = iniciarArrasto;
        };
        function iniciarArrasto(e) {
            e.preventDefault();
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            win.style.top = (win.offsetTop - pos2) + "px";
            win.style.left = (win.offsetLeft - pos1) + "px";
        }
        function fecharArrasto() { document.onmouseup = null; document.onmousemove = null; }
    }

    function tornarRedimensionavel(win) {
        const resizers = win.querySelectorAll('.resizer');
        let isResizing = false;
        for (let resizer of resizers) {
            resizer.addEventListener('mousedown', function(e) {
                if (win.classList.contains('janela-maximizada')) return;
                e.preventDefault();
                isResizing = true;
                const currentResizer = e.target;
                
                function mousemove(e) {
                    if (!isResizing) return;
                    const rect = win.getBoundingClientRect();
                    if (currentResizer.classList.contains('resizer-r') || currentResizer.classList.contains('resizer-br')) {
                        win.style.width = (e.clientX - rect.left) + 'px';
                    }
                    if (currentResizer.classList.contains('resizer-b') || currentResizer.classList.contains('resizer-br')) {
                        win.style.height = (e.clientY - rect.top) + 'px';
                    }
                    if(win.id === 'janelaRascunho') redimensionarCanvas();
                }
                function mouseup() {
                    isResizing = false;
                    window.removeEventListener('mousemove', mousemove);
                    window.removeEventListener('mouseup', mouseup);
                }
                window.addEventListener('mousemove', mousemove);
                window.addEventListener('mouseup', mouseup);
            });
        }
    }

    tornarArrastavel(document.getElementById('janelaRascunho'), document.getElementById('headerRascunho'));
    tornarRedimensionavel(document.getElementById('janelaRascunho'));
    tornarArrastavel(document.getElementById('janelaCalculadora'), document.getElementById('headerCalculadora'));
    tornarRedimensionavel(document.getElementById('janelaCalculadora'));

    // ==========================================
    // LÓGICA CALCULADORA
    // ==========================================
    const calcDisplay = document.getElementById('calcDisplay');
    let calcValor = '';

    function calcDigitar(char) {
        if (calcDisplay.innerText === '0' || calcDisplay.innerText === 'Erro') calcValor = '';
        calcValor += char;
        calcDisplay.innerText = calcValor;
    }
    function calcLimpar() { calcValor = ''; calcDisplay.innerText = '0'; }
    function calcDel() {
        if (calcValor.length > 0) {
            calcValor = calcValor.slice(0, -1);
            calcDisplay.innerText = calcValor === '' ? '0' : calcValor;
        }
    }
    function calcCalcular() {
        try {
            let expressaoJS = calcValor.replace(/×/g, '*').replace(/÷/g, '/').replace(/,/g, '.').replace(/\^/g, '**').replace(/π/g, 'Math.PI').replace(/e/g, 'Math.E').replace(/sin\(/g, 'Math.sin(').replace(/cos\(/g, 'Math.cos(').replace(/tan\(/g, 'Math.tan(').replace(/log\(/g, 'Math.log10(').replace(/√\(/g, 'Math.sqrt(');
            const resultado = Function('"use strict";return (' + expressaoJS + ')')();
            if (isNaN(resultado) || !isFinite(resultado)) throw "Erro";
            calcValor = Number.isInteger(resultado) ? resultado.toString() : parseFloat(resultado.toFixed(6)).toString();
            calcDisplay.innerText = calcValor.replace(/\./g, ',');
        } catch(e) { calcDisplay.innerText = 'Erro'; calcValor = ''; }
    }

    // ==========================================
    // LÓGICA CADERNO RASCUNHO
    // ==========================================
    const canvas = document.getElementById('canvasRascunho');
    const ctx = canvas.getContext('2d', { willReadFrequently: true });
    const container = document.getElementById('canvasContainer');
    let desenhando = false;
    let corAtual = 'black';
    let ferramentaAtual = 'lapis';
    let paginaAtual = 1;
    const maxPaginas = 30;
    const paginasSalvas = new Array(maxPaginas + 1).fill(null);
    let startX, startY;
    let snapshotImgData;

    function redimensionarCanvas() {
        salvarPaginaAtual();
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;
        carregarPaginaAtual();
    }
    window.addEventListener('resize', () => { if(document.getElementById('janelaRascunho').style.display !== 'none') redimensionarCanvas(); });

    function mudarCor(cor, btn) {
        corAtual = cor;
        document.querySelectorAll('.btn-cor').forEach(b => b.classList.remove('ativo'));
        btn.classList.add('ativo');
    }

    function setTool(tool, btn) {
        ferramentaAtual = tool;
        document.querySelectorAll('.btn-ferramenta').forEach(b => {
            if(b.innerText.includes('Lápis') || b.innerText.includes('Linha') || b.innerText.includes('Retângulo') || b.innerText.includes('Círculo') || b.innerText.includes('Borracha')) {
                b.classList.remove('ativo');
            }
        });
        btn.classList.add('ativo');
    }

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    }

    canvas.addEventListener('mousedown', (e) => {
        desenhando = true;
        const pos = getPos(e);
        startX = pos.x;
        startY = pos.y;
        ctx.beginPath();
        ctx.moveTo(startX, startY);
        snapshotImgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    });

    canvas.addEventListener('mousemove', (e) => {
        if (!desenhando) return;
        const pos = getPos(e);

        if (ferramentaAtual === 'lapis') {
            ctx.strokeStyle = corAtual;
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        } else if (ferramentaAtual === 'borracha') {
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 20;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        } else {
            ctx.putImageData(snapshotImgData, 0, 0);
            ctx.strokeStyle = corAtual;
            ctx.lineWidth = 2;
            ctx.beginPath();
            if (ferramentaAtual === 'linha') {
                ctx.moveTo(startX, startY);
                ctx.lineTo(pos.x, pos.y);
            } else if (ferramentaAtual === 'retangulo') {
                ctx.rect(startX, startY, pos.x - startX, pos.y - startY);
            } else if (ferramentaAtual === 'circulo') {
                const raio = Math.sqrt(Math.pow(pos.x - startX, 2) + Math.pow(pos.y - startY, 2));
                ctx.arc(startX, startY, raio, 0, 2 * Math.PI);
            }
            ctx.stroke();
        }
    });

    canvas.addEventListener('mouseup', () => desenhando = false);
    canvas.addEventListener('mouseleave', () => desenhando = false);

    function limparPagina() {
        if(confirm('Tem a certeza que deseja limpar esta página inteira?')) ctx.clearRect(0, 0, canvas.width, canvas.height);
    }

    function salvarPaginaAtual() {
        if (canvas.width > 0 && canvas.height > 0) paginasSalvas[paginaAtual] = canvas.toDataURL();
    }

    function carregarPaginaAtual() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (paginasSalvas[paginaAtual]) {
            const img = new Image();
            img.src = paginasSalvas[paginaAtual];
            img.onload = () => ctx.drawImage(img, 0, 0);
        }
        document.getElementById('indicadorPagina').innerText = 'Pág ' + paginaAtual;
    }

    function mudarPagina(delta) {
        salvarPaginaAtual();
        let novaPagina = paginaAtual + delta;
        if (novaPagina >= 1 && novaPagina <= maxPaginas) {
            paginaAtual = novaPagina;
            carregarPaginaAtual();
        }
    }
  </script>
</body>
</html>
