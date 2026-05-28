<?php
session_start();
require_once 'config.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    // Encripta a senha (nunca se guarda senhas em texto puro)
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $role_id = 2; // 2 = Estudante (conforme o nosso schema)

    try {
        $stmt = $pdo->prepare("INSERT INTO users (role_id, nome, email, password_hash) VALUES (:role, :nome, :email, :senha)");
        $stmt->execute([
            ':role' => $role_id,
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $senha_hash
        ]);
        $sucesso = "Conta criada com sucesso! <a href='login.php'>Faça login aqui</a>.";
    } catch (PDOException $e) {
        // Se der erro (ex: email já existe)
        $erro = "Erro ao criar conta. O email já pode estar em uso.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Registo | Atomicamente</title>
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    .auth-box { max-width: 400px; margin: 80px auto; background: white; padding: 40px; border-radius: 16px; border: 1px solid var(--borda); text-align: center; }
    .auth-input { width: 100%; padding: 12px; margin: 10px 0 20px; border: 1px solid var(--borda); border-radius: 8px; box-sizing: border-box; }
    .auth-btn { width: 100%; padding: 12px; background: var(--roxo-base); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
  </style>
</head>
<body class="dash-body">
  <div class="auth-box">
    <h2 style="color: var(--roxo-base); margin-bottom: 20px;">Criar Conta</h2>
    <?php if($erro): ?> <p style="color: red;"><?php echo $erro; ?></p> <?php endif; ?>
    <?php if($sucesso): ?> <p style="color: green;"><?php echo $sucesso; ?></p> <?php else: ?>
    <form method="POST">
      <input type="text" name="nome" class="auth-input" placeholder="O teu Nome" required>
      <input type="email" name="email" class="auth-input" placeholder="O teu Email" required>
      <input type="password" name="senha" class="auth-input" placeholder="Cria uma Senha" required>
      <button type="submit" class="auth-btn">Registrar</button>
    </form>
    <p style="margin-top: 15px; font-size: 0.9rem;">Já tens conta? <a href="login.php" style="color: var(--roxo-vivo);">Entrar</a></p>
    <?php endif; ?>
  </div>
</body>
</html>
