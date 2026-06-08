<?php
session_start();
require_once 'config.php';

$erro = '';
$sucesso = '';

$frases = [
    "A sua aprovação é uma reação em cadeia. Continue firme! ⚛️",
    "Transforme o esforço em conhecimento: a verdadeira alquimia dos estudos. 🧪",
    // ... (suas outras frases)
];
$fraseDoDia = $frases[array_rand($frases)];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $role_id = 2; // Estudante
    
    // GERA O TOKEN ÚNICO (32 caracteres aleatórios)
    $token = bin2hex(random_bytes(16));

    try {
        $stmt = $pdo->prepare("INSERT INTO users (role_id, nome, email, password_hash, email_verificado, token_verificacao) VALUES (:role, :nome, :email, :senha, 0, :token)");
        $stmt->execute([
            ':role' => $role_id,
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $senha_hash,
            ':token' => $token
        ]);
        
        // PREPARA O E-MAIL
        // Ajuste este link para o seu domínio real quando for para a Hostinger
        $link_ativacao = "http://localhost/atomicamente/ativar.php?token=" . $token; 
        
        $assunto = "Ative sua conta no Atomicamente ⚛️";
        $mensagem = "Olá, $nome!\n\n";
        $mensagem .= "Bem-vindo ao Atomicamente. Para começar a sua jornada de estudos e liberar seu acesso, clique no link abaixo para verificar seu e-mail:\n\n";
        $mensagem .= $link_ativacao . "\n\n";
        $mensagem .= "Se você não se cadastrou em nossa plataforma, ignore este e-mail.\n\n";
        $mensagem .= "Bons estudos,\nEquipe Atomicamente";
        
        $headers = "From: nao-responda@seusite.com.br\r\n"; // Mude depois para o seu e-mail oficial
        $headers .= "Reply-To: suporte@seusite.com.br\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Tenta enviar o e-mail
        @mail($email, $assunto, $mensagem, $headers);

        $sucesso = "Sua conta foi gerada com sucesso! Um link de ativação foi enviado para o seu e-mail.";
    } catch (PDOException $e) {
        $erro = "Houve um conflito na criação. Este e-mail já está associado a uma conta ativa.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Criar Conta | Atomicamente</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/plataforma.css">
  
  <style>
    /* Reset e Layout Unificado com o Login */
    body.dash-body {
        margin: 0;
        padding: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at center, #1e112a 0%, #0d0614 100%);
    }

    #bg-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        pointer-events: all;
    }

    /* Container Glassmorphism de Alta Definição */
    .auth-container {
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 420px;
        padding: 40px;
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 24px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.35);
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .auth-container:hover {
        transform: translateY(-2px);
        border-color: rgba(147, 51, 234, 0.25);
        box-shadow: 0 30px 60px rgba(147, 51, 234, 0.12);
    }

    .auth-logo {
        height: 55px;
        margin-bottom: 18px;
        filter: drop-shadow(0 0 12px rgba(147, 51, 234, 0.4));
    }

    .auth-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        color: #ffffff;
        font-size: 1.8rem;
        margin: 0 0 8px 0;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .auth-subtitle {
        color: rgba(255, 255, 255, 0.55);
        font-size: 0.9rem;
        margin: 0 0 25px 0;
        font-style: italic;
        line-height: 1.4;
    }

    .input-group {
        position: relative;
        margin-bottom: 16px;
        text-align: left;
    }

    .auth-input {
        width: 100%;
        padding: 14px 16px;
        background: rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        color: #ffffff;
        font-size: 0.95rem;
        font-family: 'Inter', sans-serif;
        box-sizing: border-box;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .auth-input:focus {
        outline: none;
        border-color: #a855f7;
        background: rgba(0, 0, 0, 0.45);
        box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.2);
    }

    .auth-input::placeholder {
        color: rgba(255, 255, 255, 0.25);
    }

    .auth-btn {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25);
        margin-top: 8px;
    }

    .auth-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
        filter: brightness(1.08);
    }

    .msg-erro {
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.2);
        color: #f87171;
        padding: 12px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 0.88rem;
    }

    .msg-sucesso {
        background: rgba(34, 197, 94, 0.08);
        border: 1px solid rgba(34, 197, 94, 0.2);
        color: #4ade80;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 0.92rem;
        line-height: 1.5;
    }

    .auth-footer {
        margin-top: 25px;
        font-size: 0.88rem;
        color: rgba(255, 255, 255, 0.45);
    }

    .auth-link {
        color: #a855f7;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }

    .auth-link:hover {
        color: #c084fc;
        text-decoration: underline;
    }
  </style>
</head>
<body class="dash-body">

  <canvas id="bg-canvas"></canvas>

  <div class="auth-container">
    <img src="assets/icone-simplificado.png" alt="Atomicamente Logo" class="auth-logo">
    <h2 class="auth-title">Criar Conta</h2>
    <p class="auth-subtitle">"<?php echo $fraseDoDia; ?>"</p>

    <?php if($erro): ?>
        <div class="msg-erro">⚠️ <?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <?php if($sucesso): ?>
        <div class="msg-sucesso">
            🎉 <?php echo $sucesso; ?>
            <br><br>
            <a href="login.php" class="auth-btn" style="display:inline-block; text-decoration:none; width:auto; padding:10px 20px;">Ir para o Login</a>
        </div>
    <?php else: ?>
        <form method="POST">
          <div class="input-group">
            <input type="text" name="nome" class="auth-input" placeholder="Como quer ser chamado?" required autocomplete="name">
          </div>
          <div class="input-group">
            <input type="email" name="email" class="auth-input" placeholder="Seu melhor e-mail" required autocomplete="email">
          </div>
          <div class="input-group">
            <input type="password" name="senha" class="auth-input" placeholder="Crie uma senha forte" required autocomplete="new-password">
          </div>
          <button type="submit" class="auth-btn">Iniciar Cadastro</button>
        </form>
        <p class="auth-footer">Já faz parte do elemento? <a href="login.php" class="auth-link">Fazer login</a></p>
    <?php endif; ?>
  </div>

  <script>
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');

    let particles = [];
    let floatingTexts = [];
    const mouse = { x: null, y: null, radius: 130 };
    
    // Lista de palavras motivacionais desordenadas que vão piscar/flutuar ao fundo
    const palavrasSuporte = ["Você consegue!", "Foco total", "Evolução", "Reação", "Constância", "Persista", "Mais um passo", "Aprovação", "Alquimia", "Mentalidade"];

    function resizeCanvas() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    window.addEventListener('mousemove', (e) => { mouse.x = e.x; mouse.y = e.y; });
    window.addEventListener('mouseout', () => { mouse.x = null; mouse.y = null; });

    // Classe das partículas atômicas
    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 2.5 + 1;
            this.speedX = (Math.random() - 0.5) * 0.6;
            this.speedY = (Math.random() - 0.5) * 0.6;
        }
        update() {
            this.x += this.speedX;
            this.y += this.speedY;

            if (this.x > canvas.width || this.x < 0) this.speedX = -this.speedX;
            if (this.y > canvas.height || this.y < 0) this.speedY = -this.speedY;

            if (mouse.x != null && mouse.y != null) {
                let dx = this.x - mouse.x;
                let dy = this.y - mouse.y;
                let distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < mouse.radius) {
                    let force = (mouse.radius - distance) / mouse.radius;
                    this.x += (dx / distance) * force * 2.5;
                    this.y += (dy / distance) * force * 2.5;
                }
            }
        }
        draw() {
            ctx.fillStyle = 'rgba(168, 85, 247, 0.35)';
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    // Classe das Frases Flutuantes (Efeito Subliminar Premium)
    class FloatingText {
        constructor() {
            this.reset();
            // Evita que comecem todas piscando no mesmo frame
            this.opacity = Math.random() * 0.2;
        }
        reset() {
            this.x = Math.random() * (canvas.width - 150) + 50;
            this.y = Math.random() * (canvas.height - 50) + 25;
            this.text = palavrasSuporte[Math.floor(Math.random() * palavrasSuporte.length)];
            this.speedY = -(Math.random() * 0.15 + 0.05); // Sobem bem devagar
            this.opacity = 0;
            this.fadeSpeed = Math.random() * 0.003 + 0.0015;
            this.state = 'fadeIn'; // fadeIn, hold, fadeOut
            this.maxOpacity = Math.random() * 0.22 + 0.08; // Super suave para não distrair
            this.holdTime = Math.random() * 100 + 50;
            this.currentHold = 0;
        }
        update() {
            this.y += this.speedY;

            if (this.state === 'fadeIn') {
                this.opacity += this.fadeSpeed;
                if (this.opacity >= this.maxOpacity) {
                    this.opacity = this.maxOpacity;
                    this.state = 'hold';
                }
            } else if (this.state === 'hold') {
                this.currentHold++;
                if (this.currentHold >= this.holdTime) {
                    this.state = 'fadeOut';
                }
            } else if (this.state === 'fadeOut') {
                this.opacity -= this.fadeSpeed;
                if (this.opacity <= 0) {
                    this.reset();
                }
            }
        }
        draw() {
            ctx.font = '500 11px "Plus Jakarta Sans", sans-serif';
            ctx.fillStyle = `rgba(216, 180, 254, ${this.opacity})`;
            ctx.letterSpacing = "1px";
            ctx.fillText(this.text, this.x, this.y);
        }
    }

    function init() {
        particles = [];
        floatingTexts = [];
        
        const numParticles = Math.floor((canvas.width * canvas.height) / 9500);
        for (let i = 0; i < numParticles; i++) {
            particles.push(new Particle());
        }

        // Quantidade controlada de palavras na tela simultaneamente
        const numTexts = Math.floor(canvas.width / 250); 
        for (let i = 0; i < numTexts; i++) {
            floatingTexts.push(new FloatingText());
        }
    }

    function connectParticles() {
        for (let a = 0; a < particles.length; a++) {
            for (let b = a; b < particles.length; b++) {
                let dx = particles[a].x - particles[b].x;
                let dy = particles[a].y - particles[b].y;
                let distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 100) {
                    let opacity = (1 - (distance / 100)) * 0.12;
                    ctx.strokeStyle = `rgba(124, 58, 237, ${opacity})`;
                    ctx.lineWidth = 0.8;
                    ctx.beginPath();
                    ctx.moveTo(particles[a].x, particles[a].y);
                    ctx.lineTo(particles[b].x, particles[b].y);
                    ctx.stroke();
                }
            }
        }
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Renderiza as palavras flutuantes por trás das linhas
        floatingTexts.forEach(t => { t.update(); t.draw(); });
        
        particles.forEach(p => { p.update(); p.draw(); });
        
        connectParticles();
        requestAnimationFrame(animate);
    }

    init();
    animate();
    window.addEventListener('resize', init);
  </script>
</body>
</html>
