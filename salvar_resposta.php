<?php
session_start();
require_once 'config.php';

// Recebe os dados enviados pelo JavaScript
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $questao_id     = $data['questao_id'];
    $alternativa_id = $data['alternativa_id'];
    $foi_correta    = $data['foi_correta'] ? 1 : 0;
    $estudante_id   = 1; // ID estático para simular a aluna atual

    try {
        // Guarda a resposta na tabela histórica
        $stmt = $pdo->prepare("INSERT INTO respostas_estudantes (estudante_id, questao_id, alternativa_id, foi_correta) 
                               VALUES (:estudante, :questao, :alternativa, :correta)");
        $stmt->execute([
            ':estudante'   => $estudante_id,
            ':questao'     => $questao_id,
            ':alternativa' => $alternativa_id,
            ':correta'     => $foi_correta
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Resposta computada!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
