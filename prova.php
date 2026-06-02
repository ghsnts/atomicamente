<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: simulado.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$qtd_questoes = (int) $_POST['qtd_questoes'];
$topicos_selecionados = $_POST['topicos'] ?? [];

if (empty($topicos_selecionados)) {
    die("Nenhum tópico selecionado.");
}

try {
    // Transforma a array num formato seguro para a Query IN (?, ?, ?)
    $inQuery = implode(',', array_fill(0, count($topicos_selecionados), '?'));
    
    // Busca questões aleatórias (ORDER BY RAND()) apenas dos tópicos escolhidos
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE subtopic_id IN ($inQuery) ORDER BY RAND() LIMIT ?");
    
    // Junta os parâmetros: IDs dos tópicos + Limite de questões
    $params = $topicos_selecionados;
    $params[] = $qtd_questoes;
    
    // Como o LIMIT precisa ser inteiro explícito, usamos bindValue
    foreach ($params as $key => $val) {
        $stmt->bindValue($key + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $questoes_prova = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tempo padrão: 3 minutos (180 seg) por questão (Padrão ENEM)
    $tempo_total_segundos = count($questoes_prova) * 180; 

} catch (PDOException $e) {
    die("Erro ao gerar a prova: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Simulado em Andamento | Atomicamente</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-global); color: var(--texto-principal); margin: 0; padding-top: 80px; }
    
    /* CABEÇALHO MODO FOCO (Sem links externos para evitar saída acidental) */
    .header-foco { position: fixed; top: 0; left: 0; width: 100%; background: var(--bg-card); border-bottom: 2px solid var(--roxo-base); padding: 15px 0; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .nav-foco { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 20px; }
    .marca-foco { font-weight: 800; font-size: 1.2rem; display: flex; align-items: center; gap: 10px; color: var(--texto-principal); }
    
    .cronometro-box { display: flex; align-items: center; gap: 10px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 8px 16px; border-radius: 12px; font-weight: 800; font-size: 1.1rem; letter-spacing: 2px; }

    .container-prova { max-width: 800px; margin: 40px auto; padding: 0 20px; }
    
    .card-questao { background: var(--bg-card); border: 1px solid var(--borda); border-radius: 20px; padding: 40px; margin-bottom: 30px; box-shadow: 0 4px 20px -5px rgba(0,0,0,0.02); }
    .badge-questao { font-size: 0.8rem; text-transform: uppercase; font-weight: 800; color: var(--roxo-base); letter-spacing: 0.05em; background: var(--roxo-suave); padding: 6px 12px; border-radius: 8px; display: inline-block; margin-bottom: 20px; }
    .enunciado { font-size: 1.15rem; line-height: 1.7; color: var(--texto-principal); font-weight: 500; margin-bottom: 30px; }
    
    .opcao-exercicio { display: flex; align-items: center; gap: 15px; padding: 16px; border: 2px solid var(--borda); border-radius: 12px; margin-bottom: 12px; cursor: pointer; transition: all 0.2s ease; background-color: var(--bg-card); }
    .opcao-exercicio:hover { border-color: var(--roxo-base); background-color: var(--roxo-suave); }
    .opcao-exercicio input[type="radio"] { width: 20px; height: 20px; accent-color: var(--roxo-base); cursor: pointer; margin: 0; }
    .letra-opcao { font-weight: 800; font-size: 1.1rem; color: var(--roxo-base); }
    
    .btn-entregar { display: block; width: 100%; text-align: center; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 20px; font-size: 1.2rem; font-weight: 800; border-radius: 16px; cursor: pointer; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); transition: all 0.3s ease; margin-top: 40px; }
    .btn-entregar:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4); }
  </style>
  <script src="js/tema.js"></script>
</head>
<body class="dash-body">

  <header class="header-foco">
    <div class="nav-foco">
      <div class="marca-foco">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 30px; border-radius: 6px;" />
        Simulado Enem
      </div>
      <div class="cronometro-box" id="displayTempo">
        ⏳ 00:00:00
      </div>
    </div>
  </header>

  <form action="resultado.php" method="POST" id="formProva">
    <div class="container-prova">
      
      <?php if (empty($questoes_prova)): ?>
        <div class="card-questao" style="text-align: center;">
          <h2 style="color: var(--roxo-base);">Ops!</h2>
          <p>Não encontramos questões suficientes nos tópicos selecionados.</p>
          <a href="simulado.php" style="color: var(--roxo-base); font-weight: bold;">Voltar e reconfigurar</a>
        </div>
      <?php else: ?>
        
        <?php $contador = 1; ?>
        <?php foreach ($questoes_prova as $q): ?>
          <div class="card-questao">
            <div class="badge-questao">Questão <?php echo $contador++; ?> de <?php echo count($questoes_prova); ?></div>
            <div class="enunciado">
              <?php echo nl2br(htmlspecialchars($q['statement'])); ?>
            </div>

            <?php 
              $stmtAlt = $pdo->prepare("SELECT * FROM alternatives WHERE question_id = :qid ORDER BY letter ASC");
              $stmtAlt->execute([':qid' => $q['id']]);
              $alternativas = $stmtAlt->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <?php foreach ($alternativas as $alt): ?>
              <label class="opcao-exercicio">
                <input type="radio" name="respostas[<?php echo $q['id']; ?>]" value="<?php echo $alt['letter']; ?>" required>
                <span class="letra-opcao"><?php echo $alt['letter']; ?>)</span>
                <span style="color: var(--texto-principal); font-size: 1rem; flex-grow: 1;">
                  <?php echo htmlspecialchars($alt['text_content']); ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>

        <button type="submit" class="btn-entregar">✅ Entregar Prova e Ver Resultado</button>
      
      <?php endif; ?>

    </div>
  </form>

  <script>
    // LÓGICA DO CRONÓMETRO REGRESSIVO
    let tempoRestante = <?php echo $tempo_total_segundos; ?>;
    const display = document.getElementById('displayTempo');

    function formatarTempo(segundos) {
        const h = Math.floor(segundos / 3600).toString().padStart(2, '0');
        const m = Math.floor((segundos % 3600) / 60).toString().padStart(2, '0');
        const s = (segundos % 60).toString().padStart(2, '0');
        return `⏳ ${h}:${m}:${s}`;
    }

    const intervalo = setInterval(() => {
        tempoRestante--;
        display.innerText = formatarTempo(tempoRestante);

        if (tempoRestante <= 300) { // Menos de 5 minutos: Fica Vermelho Piscante
            display.style.animation = "piscar 1s infinite";
        }

        if (tempoRestante <= 0) {
            clearInterval(intervalo);
            alert("O tempo esgotou! A prova será entregue automaticamente.");
            document.getElementById('formProva').submit();
        }
    }, 1000);

    // Alerta caso o aluno tente fechar a página a meio da prova
    window.onbeforeunload = function() {
        return "Tens a certeza que queres sair? O teu progresso neste simulado será perdido.";
    };
    
    // Desativa o alerta no envio normal do formulário
    document.getElementById('formProva').addEventListener('submit', function() {
        window.onbeforeunload = null;
    });
  </script>

  <style>
    @keyframes piscar {
        0% { opacity: 1; background: rgba(239, 68, 68, 0.3); }
        50% { opacity: 0.5; background: rgba(239, 68, 68, 0.1); }
        100% { opacity: 1; background: rgba(239, 68, 68, 0.3); }
    }
  </style>
</body>
</html>
