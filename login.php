<?php
session_start();
require_once 'config.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // Verifica se o usuário existe e se a senha criptografada bate certo
    if ($user && password_verify($senha, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_role'] = $user['role_id'];
        
        // Redireciona para o painel
        header("Location: dashboard.php");
        exit;
    } else {
        $erro = "Email ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Login | Atomicamente</title>
  <link rel="stylesheet" href="css/plataforma.css">
  <style>
    .auth-box { max-width: 400px; margin: 80px auto; background: white; padding: 40px; border-radius: 16px; border: 1px solid var(--borda); text-align: center; }
    .auth-input { width: 100%; padding: 12px; margin: 10px 0 20px; border: 1px solid var(--borda); border-radius: 8px; box-sizing: border-box; }
    .auth-btn { width: 100%; padding: 12px; background: var(--roxo-base); color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
  </style>
</head>
<body class="dash-body">
  <div class="auth-box">
    <img src="assets/icone-simplificado.png" alt="Logo" style="height: 50px; margin-bottom: 15px;">
    <h2 style="color: var(--roxo-base); margin-bottom: 20px;">Bem-vindo de volta!</h2>
    <?php if($erro): ?> <p style="color: red; margin-bottom: 15px;"><?php echo $erro; ?></p> <?php endif; ?>
    <form method="POST">
      <input type="email" name="email" class="auth-input" placeholder="O teu Email" required>
      <input type="password" name="senha" class="auth-input" placeholder="A tua Senha" required>
      <button type="submit" class="auth-btn">Entrar</button>
    </form>
    <p style="margin-top: 15px; font-size: 0.9rem;">Novo por aqui? <a href="cadastro.php" style="color: var(--roxo-vivo);">Criar conta</a></p>
  </div>
</body>
</html>
