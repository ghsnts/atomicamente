<?php
session_start();
require_once 'config.php'; // Faz a ligação segura à base de dados

$mensagem = "";

// Verifica se o formulário foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subtopico_id = $_POST['subtopico_id'] ?? '';
    $texto_aula   = $_POST['texto_aula'] ?? '';
    $video_url    = $_POST['video_url'] ?? '';
    $fontes       = $_POST['fontes'] ?? '';

    if (!empty($subtopico_id) && !empty($texto_aula)) {
        try {
            // Mapeia o nome bonito dependendo do ID selecionado
            $nome_amigavel = ($subtopico_id === 'modelos-atomicos') ? 'Modelos Atómicos e Distribuição' : 'Estequiometria e Leis Ponderais';

            // Query robusta: Insere a aula ou atualiza os dados caso o ID já exista
            $stmt = $pdo->prepare("INSERT INTO subtopicos (id, materia_id, nome, texto_aula, video_url, fontes) 
                                   VALUES (:id, 1, :nome, :texto, :video, :fontes)
                                   ON DUPLICATE KEY UPDATE texto_aula = :texto2, video_url = :video2, fontes = :fontes2");
            
            $stmt->execute([
                ':id'     => $subtopico_id,
                ':nome'   => $nome_amigavel,
                ':texto'  => $texto_aula,
                ':video'  => $video_url,
                ':fontes' => $fontes,
                ':texto2' => $texto_aula,
                ':video2' => $video_url,
                ':fontes2'=> $fontes
            ]);

            $mensagem = "<div style='background:#dcfce7; color:#15803d; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>✓ Conteúdo guardado e atualizado no MySQL com sucesso!</div>";
        } catch (PDOException $e) {
            $mensagem = "<div style='background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; font-weight:bold;'>❌ Erro ao salvar no banco: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Elaborar Aula | Admin</title>
  <link rel="icon" href="assets/favicon.ico" type="image/x-icon" />
  <link rel="stylesheet" href="css/plataforma.css">
</head>
<body class="dash-body">
  <header class="topo-dash">
    <div class="container nav-dash">
      <a href="admin_dashboard.php" class="marca-dash">
        <img src="assets/icone-simplificado.png" alt="Logo" style="height: 30px; border-radius: 6px;" />
        Voltar ao Painel Admin
      </a>
    </div>
  </header>

  <div class="container" style="padding: 40px 0; max-width: 700px;">
    <div style="background: white; border: 1px solid var(--borda); padding: 30px; border-radius: 16px;">
      <h2>✍️ Publicar Matéria em Texto, Vídeo e Fontes</h2>
      
      <?php echo $mensagem; ?>
      
      <form action="admin_conteudo.php" method="POST" style="display: flex; flex-direction: column; gap: 20px; margin-top: 20px;">
        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Selecionar o Tópico do ENEM:</label>
          <select name="subtopico_id" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);">
            <option value="modelos-atomicos">Química Geral -> Modelos Atómicos</option>
            <option value="estequiometria">Química Geral -> Estequiometria</option>
          </select>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Texto da Aula (Suporta parágrafos HTML):</label>
          <textarea name="texto_aula" rows="8" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);" placeholder="Insira o texto completo da aula aqui..." required></textarea>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">URL do Vídeo do YouTube (Embed):</label>
          <input type="url" name="video_url" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);" placeholder="https://www.youtube.com/embed/dQw4w9WgXcQ" required>
        </div>

        <div style="display: flex; flex-direction: column; gap: 5px;">
          <label style="font-weight: bold; color: var(--roxo-base);">Fontes Académicas e Referências:</label>
          <textarea name="fontes" rows="3" style="padding: 10px; border-radius: 8px; border: 1px solid var(--borda);" placeholder="Ex: Livro Primal de Química, Edição 2026..." required></textarea>
        </div>

        <button type="submit" style="background: var(--roxo-base); color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; cursor: pointer;">Publicar Aula Completa</button>
      </form>
    </div>
  </div>
</body>
</html>
