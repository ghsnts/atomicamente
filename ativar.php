<?php
session_start();
require_once 'config.php';

$mensagem = '';
$sucesso = false;

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    // Busca o usuário com este token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE token_verificacao = :token AND email_verificado = 0");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if ($user) {
        // Atualiza a conta para verificada e limpa o token por segurança
        $updateStmt = $pdo->prepare("UPDATE users SET email_verificado = 1, token_verificacao = NULL WHERE id = :id");
        $updateStmt->execute([':id' => $user['id']]);
        
        $mensagem = "Conta ativada com sucesso! A sua reação química pode começar.";
        $sucesso = true;
    } else {
        $mensagem = "Link inválido, expirado ou a conta já foi ativada anteriormente.";
    }
} else {
    $mensagem = "Nenhum código de ativação fornecido.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ativação | Atomicamente</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    body.dash-body {
        margin: 0; padding: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
        overflow: hidden; position: relative; font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at center, #1e112a 0%, #0d0614 100%);
    }
    #bg-canvas { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; pointer-events: all; }
    .auth-container {
        position: relative; z-index: 2; width: 100%; max-width: 420px; padding: 40px;
        background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 24px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.35);
        text-align: center;
    }
    .auth-logo { height: 60px; margin-bottom: 20px; filter: drop-shadow(0 0 12px rgba(147, 51, 234, 0.4)); }
    .auth-title { font-family: 'Plus Jakarta Sans', sans-serif; color: #ffffff; font-size: 1.8rem; margin: 0 0 20px 0; font-weight: 800; }
    
    .status-box {
        padding: 20px; border-radius: 16px; margin-bottom: 25px; font-size: 1rem; line-height: 1.5;
        background: <?php echo $sucesso ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)'; ?>;
        border: 1px solid <?php echo $sucesso ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.3)'; ?>;
        color: <?php echo $sucesso ? '#4ade80' : '#f87171'; ?>;
    }

    .auth-btn {
        display: inline-block; width: 100%; padding: 14px; background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
        color: white; border: none; border-radius: 12px; font-size: 1rem; font-weight: 600; text-decoration: none;
        box-sizing: border-box; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25);
    }
    .auth-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4); filter: brightness(1.08); }
  </style>
</head>
<body class="dash-body">

  <canvas id="bg-canvas"></canvas>

  <div class="auth-container">
    <img src="assets/icone-simplificado.png" alt="Atomicamente Logo" class="auth-logo">
    <h2 class="auth-title">Verificação de Conta</h2>
    
    <div class="status-box">
        <?php echo $sucesso ? '🎉 ' : '⚠️ '; echo htmlspecialchars($mensagem); ?>
    </div>

    <a href="login.php" class="auth-btn">Ir para o Login</a>
  </div>

  <script>
    // Usando o mesmo motor Canvas reduzido para não poluir o código
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let particles = [];
    const mouse = { x: null, y: null, radius: 130 };

    function resizeCanvas() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
    window.addEventListener('resize', resizeCanvas); resizeCanvas();
    window.addEventListener('mousemove', (e) => { mouse.x = e.x; mouse.y = e.y; });
    window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });

    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width; this.y = Math.random() * canvas.height;
            this.size = Math.random() * 2.5 + 1; this.speedX = (Math.random() - 0.5) * 0.6; this.speedY = (Math.random() - 0.5) * 0.6;
        }
        update() {
            this.x += this.speedX; this.y += this.speedY;
            if (this.x > canvas.width || this.x < 0) this.speedX = -this.speedX;
            if (this.y > canvas.height || this.y < 0) this.speedY = -this.speedY;
        }
        draw() {
            ctx.fillStyle = 'rgba(168, 85, 247, 0.35)'; ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill();
        }
    }

    function init() {
        particles = [];
        const numParticles = Math.floor((canvas.width * canvas.height) / 9500);
        for (let i = 0; i < numParticles; i++) particles.push(new Particle());
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => { p.update(); p.draw(); });
        
        // Conexões
        for (let a = 0; a < particles.length; a++) {
            for (let b = a; b < particles.length; b++) {
                let dx = particles[a].x - particles[b].x; let dy = particles[a].y - particles[b].y;
                let distance = Math.sqrt(dx*dx + dy*dy);
                if (distance < 100) {
                    ctx.strokeStyle = `rgba(124, 58, 237, ${(1 - (distance / 100)) * 0.12})`;
                    ctx.lineWidth = 0.8; ctx.beginPath(); ctx.moveTo(particles[a].x, particles[a].y); ctx.lineTo(particles[b].x, particles[b].y); ctx.stroke();
                }
            }
        }
        requestAnimationFrame(animate);
    }
    init(); animate();
  </script>
</body>
</html>
